<?php

namespace WP_CLI\Block;

use WP_CLI_Command;

/**
 * Manages WordPress block editor blocks and related entities.
 *
 * This command provides tools for working with the WordPress block editor,
 * including block types, patterns, styles, bindings, templates, and synced patterns.
 *
 * ## EXAMPLES
 *
 *     # List all registered block types
 *     $ wp block type list
 *
 *     # Get a specific block pattern
 *     $ wp block pattern get my-theme/hero
 *
 *     # List block styles for a specific block
 *     $ wp block style list --block=core/button
 *
 *     # Export a block template
 *     $ wp block template export twentytwentyfour//single --stdout
 *
 *     # Create a synced pattern
 *     $ wp block synced-pattern create --title="My Pattern" --content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'
 *
 * @package wp-cli
 */
class Block_Command extends WP_CLI_Command {
}
