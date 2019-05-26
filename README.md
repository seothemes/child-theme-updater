# Child Theme Updater

Allows modified child themes to receive automatic updates without losing changes. It works by duplicating the child theme before running an update, excluding the vendor directory. Once the update is complete, the duplicate style.css version number is updated and then all of the duplicated files are copied back to the new theme version, except for the vendor directory. Basically the only files that are updated are in the vendor directory, everything else is untouched.

## Installation

__Composer (recommended)__

Run the following command from the child theme root directory:

```shell
composer require seothemes/child-theme-updater
```

__Manual__

You could probably manually copy and paste the `child-theme-updater.php` file somewhere in your project but it's not recommended. Take the time to learn the basics of Composer and use that method instead.

## Usage

Child Theme Updater is automatically loaded when installed with Composer.

It does not include an actual theme update checker, for this we recommend [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) for publicly hosted repositories, or Easy Digital Downloads [Software Licensing](https://easydigitaldownloads.com/downloads/software-licensing/) for private, licensed themes.

It's purpose is to prevent the loss of user customizations made to a child theme during the update process.

By default, it will only allow the `vendor` directory of the child theme to be updated. The `child_theme_updater_skip` filter can be used to change or add extra directories, e.g:

```php
add_filter( 'child_theme_updater_skip', 'my_custom_directory' );
/**
 * Add `core` to the list of updateable directories.
 *
 * @since 1.0.0
 *
 * @param array $defaults List of directories that are changed during an update.
 *
 * @return array
 */
function my_custom_directory( $defaults ) {
	return array_merge( [ 'core' ], $defaults );
}
```

