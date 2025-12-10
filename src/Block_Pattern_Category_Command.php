<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;
use WP_Block_Pattern_Categories_Registry;

/**
 * Retrieves details on registered block pattern categories.
 *
 * Get information on block pattern categories from the
 * WP_Block_Pattern_Categories_Registry.
 *
 * ## EXAMPLES
 *
 *     # List all registered pattern categories
 *     $ wp block pattern-category list
 *
 *     # Get details about a specific category
 *     $ wp block pattern-category get featured --format=json
 *
 * @package wp-cli
 */
class Block_Pattern_Category_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'name',
		'label',
		'description',
	];

	/**
	 * Lists registered block pattern categories.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each category.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific category fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - yaml
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each category:
	 *
	 * * name
	 * * label
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # List all pattern categories
	 *     $ wp block pattern-category list
	 *
	 *     # Get category names only
	 *     $ wp block pattern-category list --field=name
	 *
	 *     # Export categories to JSON
	 *     $ wp block pattern-category list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$registry   = WP_Block_Pattern_Categories_Registry::get_instance();
		$categories = $registry->get_all_registered();

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = wp_list_pluck( $categories, 'name' );
			echo implode( ' ', $ids );
			return;
		}

		$items = array_map( [ $this, 'category_to_array' ], $categories );

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block pattern category.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Category name (e.g., 'buttons', 'columns').
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole category, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details about the 'buttons' category
	 *     $ wp block pattern-category get buttons
	 *
	 *     # Get as JSON
	 *     $ wp block pattern-category get featured --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$registry = WP_Block_Pattern_Categories_Registry::get_instance();
		$category = $registry->get_registered( $args[0] );

		if ( ! $category ) {
			WP_CLI::error( "Block pattern category '{$args[0]}' is not registered." );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->category_to_array( $category );

		$formatter->display_item( $data );
	}

	/**
	 * Converts a category array to a standardized associative array.
	 *
	 * @param array $category Category array from registry.
	 * @return array
	 */
	private function category_to_array( $category ) {
		return [
			'name'        => isset( $category['name'] ) ? $category['name'] : '',
			'label'       => isset( $category['label'] ) ? $category['label'] : '',
			'description' => isset( $category['description'] ) ? $category['description'] : '',
		];
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block-pattern-category' );
	}
}
