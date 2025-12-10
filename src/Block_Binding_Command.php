<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Retrieves details on registered block binding sources.
 *
 * Get information on block binding sources from the WP_Block_Bindings_Registry.
 * Block bindings allow dynamic data to be connected to block attributes.
 *
 * ## EXAMPLES
 *
 *     # List all registered binding sources
 *     $ wp block binding list
 *
 *     # Get details about the post-meta binding
 *     $ wp block binding get core/post-meta --format=json
 *
 * @package wp-cli
 */
class Block_Binding_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'name',
		'label',
	];

	/**
	 * Lists registered block binding sources.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each source.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific source fields.
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
	 * These fields will be displayed by default for each source:
	 *
	 * * name
	 * * label
	 *
	 * These fields are optionally available:
	 *
	 * * uses_context
	 *
	 * ## EXAMPLES
	 *
	 *     # List all binding sources
	 *     $ wp block binding list
	 *
	 *     # Get source names only
	 *     $ wp block binding list --field=name
	 *
	 *     # Export sources to JSON
	 *     $ wp block binding list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$sources = get_all_registered_block_bindings_sources();

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = array_keys( $sources );
			echo implode( ' ', $ids );
			return;
		}

		$items = [];
		foreach ( $sources as $name => $source ) {
			$items[] = $this->source_to_array( $name, $source );
		}

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block binding source.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Binding source name (e.g., 'core/post-meta').
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole source, returns the value of a single field.
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
	 *     # Get details about the post-meta binding
	 *     $ wp block binding get core/post-meta
	 *
	 *     # Get as JSON
	 *     $ wp block binding get core/post-meta --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$source = get_block_bindings_source( $args[0] );

		if ( ! $source ) {
			WP_CLI::error( "Block binding source '{$args[0]}' is not registered." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_merge(
				$this->fields,
				[
					'uses_context',
				]
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->source_to_array( $args[0], $source );

		$formatter->display_item( $data );
	}

	/**
	 * Converts a binding source to a standardized associative array.
	 *
	 * @param string                      $name   Source name.
	 * @param WP_Block_Bindings_Source $source Source object.
	 * @return array
	 */
	private function source_to_array( $name, $source ) {
		return [
			'name'         => $name,
			'label'        => $source->label,
			'uses_context' => $source->uses_context,
		];
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block-binding' );
	}
}
