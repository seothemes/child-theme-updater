<?php
/**
 * Child Theme Updater.
 *
 * Allows modified child themes to receive automatic updates without losing changes.
 * It works by duplicating the child theme before running an update, excluding the
 * vendor directory. Once the update is complete, the duplicate style.css version
 * number is updated and then all of the duplicated files are copied back to the
 * new theme version, except for the vendor directory. Basically the only files
 * that are updated are in the vendor directory, everything else is untouched.
 *
 * @package SeoThemes\ChildThemeUpdater
 * @author  SEO Themes
 * @license GPL-3.0-or-later
 * @link    https://github.com/seothemes/child-theme-updater
 */

namespace SeoThemes\ChildThemeUpdater;

add_action( 'upgrader_source_selection', __NAMESPACE__ . '\before_update', 10, 4 );
/**
 * Duplicates the original theme. Runs before theme update.
 *
 * @since 1.0.0
 *
 * @param string       $source        File source location.
 * @param string       $remote_source Remote file source location.
 * @param \WP_Upgrader $theme_object  WP_Upgrader instance.
 * @param array        $hook_extra    Extra arguments passed to hooked filters.
 *
 * @return string
 */
function before_update( $source, $remote_source, $theme_object, $hook_extra ) {

	// Return early if there is an error or if it's not a theme update.
	if ( is_wp_error( $source ) || ! is_a( $theme_object, 'Theme_Upgrader' ) ) {
		return $source;
	}

	// Setup WP_Filesystem.
	include_once ABSPATH . 'wp-admin /includes/file.php';
	\WP_Filesystem();
	global $wp_filesystem;

	// Create theme backup.
	$src  = \get_stylesheet_directory();
	$dest = get_theme_backup_path();

	\wp_mkdir_p( $dest );
	\copy_dir( $src, $dest, [] );

	// Stop update if backup failed.
	if ( ! file_exists( $dest . '/functions.php' ) ) {
		$source = false;
	}

	return $source;
}

add_action( 'upgrader_post_install', __NAMESPACE__ . '\after_update', 10, 3 );
/**
 * Add customizations to new version. Runs after theme update.
 *
 * @since 1.0.0
 *
 * @param bool  $response   Installation response.
 * @param array $hook_extra Extra arguments passed to hooked filters.
 * @param array $result     Installation result data.
 *
 * @return bool
 */
function after_update( $response, $hook_extra, $result ) {

	// Return early if no response or destination does not exist.
	if ( ! $response || ! array_key_exists( 'destination', $result ) ) {
		return $response;
	}

	// Setup WP_Filesystem.
	include_once ABSPATH . 'wp-admin/includes/file.php';
	\WP_Filesystem();
	global $wp_filesystem;

	// Bump temp style sheet version.
	$theme_headers = [
		'Name'    => 'Theme Name',
		'Version' => 'Version',
	];
	$new_theme     = \get_stylesheet_directory() . '/style.css';
	$new_data      = \get_file_data( $new_theme, $theme_headers );
	$new_version   = $new_data['Version'];
	$old_theme     = get_theme_backup_path() . '/style.css';
	$old_data      = \get_file_data( $old_theme, $theme_headers );
	$old_version   = $old_data['Version'];
	$old_contents  = $wp_filesystem->get_contents( $old_theme );
	$new_contents  = str_replace( $old_version, $new_version, $old_contents );

	$wp_filesystem->put_contents( $old_theme, $new_contents, FS_CHMOD_FILE );

	// Bring everything back except vendor directory.
	$target = \get_stylesheet_directory();
	$source = get_theme_backup_path();
	$skip   = apply_filters( 'child_theme_updater_skip', [ 'vendor' ] );

	\copy_dir( $source, $target, $skip );

	// Rename backup theme.
	$old_name    = $old_data['Name'];
	$new_name    = $old_name . ' Backup ' . $old_version;
	$new_content = str_replace( $old_name, $new_name, $old_contents );

	$wp_filesystem->put_contents( $old_theme, $new_content, FS_CHMOD_FILE );

	// Maybe delete theme backup (not recommended).
	if ( apply_filters( 'child_theme_updater_delete_backup', false ) ) {
		$wp_filesystem->delete( $source, true, 'd' );
	}

	return $response;
}

/**
 * Returns the path to the theme backup.
 *
 * @since 1.0.0
 *
 * @return string
 */
function get_theme_backup_path() {
	$theme   = \get_stylesheet();
	$dir     = \dirname( \get_stylesheet_directory() );
	$version = \wp_get_theme()->get( 'Version' );

	return "{$dir}/{$theme}-backup-{$version}";
}

/**
 * Get Github repository URL from stylesheet header.
 *
 * @since 1.0.0
 *
 * @param string $key Key to retrieve.
 *
 * @return string
 */
function get_github_data( $key = 'repo' ) {
	$file = \get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'style.css';
	$data = \get_file_data( $file, [
		'repo' => 'Github URI',
	] );

	return $data[ $key ];
}

add_action( 'init', __NAMESPACE__ . '\load_plugin_update_checker' );
/**
 * Maybe load plugin update checker.
 *
 * @since 1.0.0
 *
 * @return void
 */
function load_plugin_update_checker() {
	if ( ! class_exists( 'Puc_v4p6_Factory' ) ) {
		return;
	}

	$defaults = \apply_filters( 'child_theme_updater', [
		'repo'   => get_github_data(),
		'file'   => \get_stylesheet_directory(),
		'theme'  => \get_stylesheet(),
		'token'  => '',
		'branch' => 'master',
	] );

	$plugin_update_checker = \Puc_v4_Factory::buildUpdateChecker(
		$defaults['repo'],
		$defaults['file'],
		$defaults['theme']
	);
	$plugin_update_checker->setBranch( $defaults['branch'] );

	if ( '' !== $defaults['token'] ) {
		$plugin_update_checker->setAuthentication( $defaults['token'] );
	}
}
