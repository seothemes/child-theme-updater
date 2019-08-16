<?php

namespace SeoThemes\ChildThemeUpdater;

/**
 * Class ChildThemeUpdater
 *
 * @package SeoThemes\ChildThemeUpdater
 */
class Updater {

	/**
	 * ChildThemeUpdater constructor.
	 */
	public function __construct() {}

	/**
	 * Register hooks with WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'upgrader_source_selection', [ $this, 'before_update' ], 10, 4 );
		add_action( 'upgrader_post_install', [ $this, 'after_update' ], 10, 3 );
	}

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
	public function before_update( $source, $remote_source, $theme_object, $hook_extra ) {

		// Return early if there is an error or if it's not a theme update.
		if ( is_wp_error( $source ) || ! is_a( $theme_object, 'Theme_Upgrader' ) ) {
			return $source;
		}

		// Create theme backup.
		$origin = \get_stylesheet_directory();
		$backup = $this->get_theme_backup_path();

		\wp_mkdir_p( $backup );
		\copy_dir( $origin, $backup, [] );

		// Stop update if backup failed.
		if ( ! file_exists( $backup . '/functions.php' ) ) {
			$source = false;
		}

		return $source;
	}

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
	public function after_update( $response, $hook_extra, $result ) {

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
		$old_theme     = $this->get_theme_backup_path() . '/style.css';
		$old_data      = \get_file_data( $old_theme, $theme_headers );
		$old_version   = $old_data['Version'];
		$old_contents  = $wp_filesystem->get_contents( $old_theme );
		$new_contents  = str_replace( $old_version, $new_version, $old_contents );

		$wp_filesystem->put_contents( $old_theme, $new_contents, FS_CHMOD_FILE );

		// Bring everything back except vendor directory.
		$target = \get_stylesheet_directory();
		$source = $this->get_theme_backup_path();
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
	private function get_theme_backup_path() {
		$theme   = \get_stylesheet_directory();
		$version = \wp_get_theme()->get( 'Version' );

		return "{$theme}-backup-{$version}";
	}
}
