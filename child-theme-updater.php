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

$child_theme_updater = new Updater();
$child_theme_updater->run();
