<?php

use WP_CLI\Utils;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$wpcli_block_autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $wpcli_block_autoloader ) ) {
	require_once $wpcli_block_autoloader;
}

// Version-specific before_invoke callbacks.
$wpcli_block_before_invoke_5_0 = static function () {
	if ( Utils\wp_version_compare( '5.0', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 5.0 or greater.' );
	}
};

$wpcli_block_before_invoke_5_3 = static function () {
	if ( Utils\wp_version_compare( '5.3', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 5.3 or greater.' );
	}
};

$wpcli_block_before_invoke_5_5 = static function () {
	if ( Utils\wp_version_compare( '5.5', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 5.5 or greater.' );
	}
};

$wpcli_block_before_invoke_5_9 = static function () {
	if ( Utils\wp_version_compare( '5.9', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 5.9 or greater.' );
	}
};

$wpcli_block_before_invoke_6_5 = static function () {
	if ( Utils\wp_version_compare( '6.5', '<' ) ) {
		WP_CLI::error( 'Requires WordPress 6.5 or greater.' );
	}
};

// Register the namespace command for better help screens.
WP_CLI::add_command( 'block', WP_CLI\Block\Block_Command::class );

// Register commands with appropriate version checks.
WP_CLI::add_command( 'block type', WP_CLI\Block\Block_Type_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_5_0 ] );
WP_CLI::add_command( 'block pattern', WP_CLI\Block\Block_Pattern_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_5_5 ] );
WP_CLI::add_command( 'block pattern-category', WP_CLI\Block\Block_Pattern_Category_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_5_5 ] );
WP_CLI::add_command( 'block style', WP_CLI\Block\Block_Style_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_5_3 ] );
WP_CLI::add_command( 'block binding', WP_CLI\Block\Block_Binding_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_6_5 ] );
WP_CLI::add_command( 'block template', WP_CLI\Block\Block_Template_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_5_9 ] );
WP_CLI::add_command( 'block synced-pattern', WP_CLI\Block\Block_Synced_Pattern_Command::class, [ 'before_invoke' => $wpcli_block_before_invoke_5_0 ] );
