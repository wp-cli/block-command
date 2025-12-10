<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;
use WP_Block_Patterns_Registry;

/**
 * Retrieves details on registered block patterns.
 *
 * Get information on WordPress' built-in and custom block patterns from the
 * WP_Block_Patterns_Registry.
 *
 * ## EXAMPLES
 *
 *     # List all registered block patterns
 *     $ wp block pattern list
 *
 *     # Get details about a specific pattern
 *     $ wp block pattern get core/query-standard-posts --format=json
 *
 *     # List patterns in a specific category
 *     $ wp block pattern list --category=featured
 *
 * @package wp-cli
 */
class Block_Pattern_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'name',
		'title',
		'description',
		'categories',
	];

	/**
	 * Lists registered block patterns.
	 *
	 * ## OPTIONS
	 *
	 * [--category=<category>]
	 * : Filter by pattern category.
	 *
	 * [--search=<search>]
	 * : Search patterns by title or keywords.
	 *
	 * [--inserter]
	 * : Only show patterns visible in the inserter.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each pattern.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific pattern fields.
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
	 * These fields will be displayed by default for each pattern:
	 *
	 * * name
	 * * title
	 * * description
	 * * categories
	 *
	 * These fields are optionally available:
	 *
	 * * content
	 * * keywords
	 * * blockTypes
	 * * postTypes
	 * * templateTypes
	 * * inserter
	 * * viewportWidth
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered patterns
	 *     $ wp block pattern list
	 *
	 *     # List patterns in the 'buttons' category
	 *     $ wp block pattern list --category=buttons
	 *
	 *     # Search for hero patterns
	 *     $ wp block pattern list --search=hero
	 *
	 *     # Export all patterns to JSON
	 *     $ wp block pattern list --format=json > patterns.json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$registry = WP_Block_Patterns_Registry::get_instance();
		$patterns = $registry->get_all_registered();

		// Filter by category.
		if ( ! empty( $assoc_args['category'] ) ) {
			$category = $assoc_args['category'];
			$patterns = array_filter(
				$patterns,
				function ( $pattern ) use ( $category ) {
					return isset( $pattern['categories'] ) && in_array( $category, $pattern['categories'], true );
				}
			);
			unset( $assoc_args['category'] );
		}

		// Filter by search term.
		if ( ! empty( $assoc_args['search'] ) ) {
			$search   = strtolower( $assoc_args['search'] );
			$patterns = array_filter(
				$patterns,
				function ( $pattern ) use ( $search ) {
					// Search in title.
					if ( isset( $pattern['title'] ) && strpos( strtolower( $pattern['title'] ), $search ) !== false ) {
						return true;
					}
					// Search in keywords.
					if ( isset( $pattern['keywords'] ) && is_array( $pattern['keywords'] ) ) {
						foreach ( $pattern['keywords'] as $keyword ) {
							if ( strpos( strtolower( $keyword ), $search ) !== false ) {
								return true;
							}
						}
					}
					return false;
				}
			);
			unset( $assoc_args['search'] );
		}

		// Filter by inserter visibility.
		if ( isset( $assoc_args['inserter'] ) ) {
			$patterns = array_filter(
				$patterns,
				function ( $pattern ) {
					return ! isset( $pattern['inserter'] ) || false !== $pattern['inserter'];
				}
			);
			unset( $assoc_args['inserter'] );
		}

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = wp_list_pluck( $patterns, 'name' );
			echo implode( ' ', $ids );
			return;
		}

		$items = array_map( [ $this, 'pattern_to_array' ], $patterns );

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block pattern.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Pattern name including namespace (e.g., 'core/query-standard-posts').
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole pattern, returns the value of a single field.
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
	 *     # Get a pattern's content
	 *     $ wp block pattern get core/query-standard-posts --field=content
	 *
	 *     # Get full pattern details as JSON
	 *     $ wp block pattern get my-theme/hero --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$registry = WP_Block_Patterns_Registry::get_instance();
		$pattern  = $registry->get_registered( $args[0] );

		if ( ! $pattern ) {
			WP_CLI::error( "Block pattern '{$args[0]}' is not registered." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_merge(
				$this->fields,
				[
					'content',
					'keywords',
					'blockTypes',
					'postTypes',
					'templateTypes',
					'inserter',
					'viewportWidth',
				]
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->pattern_to_array( $pattern );

		$formatter->display_item( $data );
	}

	/**
	 * Converts a pattern array to a standardized associative array.
	 *
	 * @param array $pattern Pattern array from registry.
	 * @return array
	 */
	private function pattern_to_array( $pattern ) {
		return [
			'name'          => isset( $pattern['name'] ) ? $pattern['name'] : '',
			'title'         => isset( $pattern['title'] ) ? $pattern['title'] : '',
			'description'   => isset( $pattern['description'] ) ? $pattern['description'] : '',
			'categories'    => isset( $pattern['categories'] ) ? $pattern['categories'] : [],
			'content'       => isset( $pattern['content'] ) ? $pattern['content'] : '',
			'keywords'      => isset( $pattern['keywords'] ) ? $pattern['keywords'] : [],
			'blockTypes'    => isset( $pattern['blockTypes'] ) ? $pattern['blockTypes'] : [],
			'postTypes'     => isset( $pattern['postTypes'] ) ? $pattern['postTypes'] : [],
			'templateTypes' => isset( $pattern['templateTypes'] ) ? $pattern['templateTypes'] : [],
			'inserter'      => isset( $pattern['inserter'] ) ? $pattern['inserter'] : true,
			'viewportWidth' => isset( $pattern['viewportWidth'] ) ? $pattern['viewportWidth'] : null,
		];
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block-pattern' );
	}
}
