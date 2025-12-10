<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;
use WP_Block_Styles_Registry;

/**
 * Retrieves details on registered block styles.
 *
 * Get information on block style variations from the WP_Block_Styles_Registry.
 *
 * ## EXAMPLES
 *
 *     # List all registered block styles
 *     $ wp block style list
 *
 *     # List styles for a specific block
 *     $ wp block style list --block=core/button
 *
 *     # Get details about a specific style
 *     $ wp block style get core/button outline
 *
 * @package wp-cli
 */
class Block_Style_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'block_name',
		'name',
		'label',
		'is_default',
	];

	/**
	 * Lists registered block styles.
	 *
	 * ## OPTIONS
	 *
	 * [--block=<block>]
	 * : Filter by block type name (e.g., 'core/button').
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each style.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific style fields.
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
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each style:
	 *
	 * * block_name
	 * * name
	 * * label
	 * * is_default
	 *
	 * These fields are optionally available:
	 *
	 * * style_handle
	 * * inline_style
	 *
	 * ## EXAMPLES
	 *
	 *     # List all block styles
	 *     $ wp block style list
	 *
	 *     # List styles for the button block
	 *     $ wp block style list --block=core/button
	 *
	 *     # List all styles as JSON
	 *     $ wp block style list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$registry = WP_Block_Styles_Registry::get_instance();

		$items = [];

		// Filter by specific block or get all.
		if ( ! empty( $assoc_args['block'] ) ) {
			$block_name = $assoc_args['block'];
			$styles     = $registry->get_registered_styles_for_block( $block_name );
			foreach ( $styles as $style_name => $style ) {
				$items[] = $this->style_to_array( $block_name, $style_name, $style );
			}
			unset( $assoc_args['block'] );
		} else {
			// Get all registered styles for all blocks.
			$all_styles = $registry->get_all_registered();
			foreach ( $all_styles as $block_name => $styles ) {
				foreach ( $styles as $style_name => $style ) {
					$items[] = $this->style_to_array( $block_name, $style_name, $style );
				}
			}
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block style.
	 *
	 * ## OPTIONS
	 *
	 * <block>
	 * : Block type name (e.g., 'core/button').
	 *
	 * <style>
	 * : Style name (e.g., 'outline').
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole style, returns the value of a single field.
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
	 *     # Get the outline style for buttons
	 *     $ wp block style get core/button outline
	 *
	 *     # Get as JSON
	 *     $ wp block style get core/button outline --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$block_name = $args[0];
		$style_name = $args[1];

		$registry = WP_Block_Styles_Registry::get_instance();
		$styles   = $registry->get_registered_styles_for_block( $block_name );

		if ( ! isset( $styles[ $style_name ] ) ) {
			WP_CLI::error( "Block style '{$style_name}' for block '{$block_name}' is not registered." );
		}

		$style = $styles[ $style_name ];

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_merge(
				$this->fields,
				[
					'style_handle',
					'inline_style',
				]
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->style_to_array( $block_name, $style_name, $style );

		$formatter->display_item( $data );
	}

	/**
	 * Converts a style array to a standardized associative array.
	 *
	 * @param string $block_name Block type name.
	 * @param string $style_name Style name.
	 * @param array  $style      Style array from registry.
	 * @return array
	 */
	private function style_to_array( $block_name, $style_name, $style ) {
		return [
			'block_name'   => $block_name,
			'name'         => $style_name,
			'label'        => isset( $style['label'] ) ? $style['label'] : '',
			'is_default'   => isset( $style['is_default'] ) ? $style['is_default'] : false,
			'style_handle' => isset( $style['style_handle'] ) ? $style['style_handle'] : '',
			'inline_style' => isset( $style['inline_style'] ) ? $style['inline_style'] : '',
		];
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block-style' );
	}
}
