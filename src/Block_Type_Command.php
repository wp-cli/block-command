<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;
use WP_Block_Type;
use WP_Block_Type_Registry;

/**
 * Retrieves details on registered block types.
 *
 * Get information on WordPress' built-in and custom block types from the
 * WP_Block_Type_Registry.
 *
 * ## EXAMPLES
 *
 *     # List all registered block types
 *     $ wp block type list
 *
 *     # Get details about a specific block type
 *     $ wp block type get core/paragraph --format=json
 *
 *     # List all core blocks
 *     $ wp block type list --namespace=core
 *
 * @package wp-cli
 */
class Block_Type_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'name',
		'title',
		'description',
		'category',
		'is_dynamic',
	];

	/**
	 * Lists registered block types.
	 *
	 * ## OPTIONS
	 *
	 * [--namespace=<namespace>]
	 * : Filter by block namespace (e.g., 'core', 'my-plugin').
	 *
	 * [--dynamic]
	 * : Only show dynamic blocks (blocks with render_callback).
	 *
	 * [--static]
	 * : Only show static blocks (blocks without render_callback).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each block type.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific block type fields.
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
	 * These fields will be displayed by default for each block type:
	 *
	 * * name
	 * * title
	 * * description
	 * * category
	 * * is_dynamic
	 *
	 * These fields are optionally available:
	 *
	 * * icon
	 * * keywords
	 * * parent
	 * * ancestor
	 * * supports
	 * * attributes
	 * * provides_context
	 * * uses_context
	 * * block_hooks
	 * * editor_script_handles
	 * * script_handles
	 * * view_script_handles
	 * * editor_style_handles
	 * * style_handles
	 * * view_style_handles
	 * * api_version
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered block types
	 *     $ wp block type list
	 *
	 *     # List all core blocks
	 *     $ wp block type list --namespace=core --fields=name,title,category
	 *
	 *     # List only dynamic blocks
	 *     $ wp block type list --dynamic --format=json
	 *
	 *     # Get count of registered block types
	 *     $ wp block type list --format=count
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		// Validate mutually exclusive flags.
		$dynamic = isset( $assoc_args['dynamic'] );
		$static  = isset( $assoc_args['static'] );

		if ( $dynamic && $static ) {
			WP_CLI::error( '--dynamic and --static are mutually exclusive.' );
		}

		$registry    = WP_Block_Type_Registry::get_instance();
		$block_types = $registry->get_all_registered();

		// Filter by namespace.
		if ( ! empty( $assoc_args['namespace'] ) ) {
			$namespace   = $assoc_args['namespace'];
			$block_types = array_filter(
				$block_types,
				function ( $block_type ) use ( $namespace ) {
					return strpos( $block_type->name, $namespace . '/' ) === 0;
				}
			);
			unset( $assoc_args['namespace'] );
		}

		// Filter by dynamic/static.
		if ( isset( $assoc_args['dynamic'] ) ) {
			$block_types = array_filter(
				$block_types,
				function ( $block_type ) {
					return $block_type->is_dynamic();
				}
			);
			unset( $assoc_args['dynamic'] );
		} elseif ( isset( $assoc_args['static'] ) ) {
			$block_types = array_filter(
				$block_types,
				function ( $block_type ) {
					return ! $block_type->is_dynamic();
				}
			);
			unset( $assoc_args['static'] );
		}

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = array_keys( $block_types );
			echo implode( ' ', $ids );
			return;
		}

		$items = [];
		foreach ( $block_types as $block_type ) {
			$items[] = $this->block_type_to_array( $block_type );
		}

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block type.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Block type name (e.g., 'core/paragraph').
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole block type, returns the value of a single field.
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
	 *     # Get details about the paragraph block
	 *     $ wp block type get core/paragraph
	 *
	 *     # Get the supports field as JSON
	 *     $ wp block type get core/paragraph --field=supports --format=json
	 *
	 *     # Get specific fields
	 *     $ wp block type get core/image --fields=name,title,supports --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $args[0] );

		if ( ! $block_type ) {
			WP_CLI::error( "Block type '{$args[0]}' is not registered." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_merge(
				$this->fields,
				[
					'icon',
					'keywords',
					'parent',
					'ancestor',
					'supports',
					'attributes',
					'provides_context',
					'uses_context',
					'block_hooks',
					'api_version',
				]
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->block_type_to_array( $block_type );

		$formatter->display_item( $data );
	}

	/**
	 * Checks whether a block type is registered.
	 *
	 * Exits with return code 0 if the block type exists, 1 if it does not.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The block type name, including namespace.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check if a block type exists.
	 *     $ wp block type exists core/paragraph
	 *     Success: Block type 'core/paragraph' is registered.
	 *
	 *     # Check for a non-existent block type.
	 *     $ wp block type exists core/nonexistent
	 *     $ echo $?
	 *     1
	 *
	 * @subcommand exists
	 *
	 * @param array $args Positional arguments.
	 */
	public function exists( $args ) {
		$registry   = WP_Block_Type_Registry::get_instance();
		$block_type = $registry->get_registered( $args[0] );

		if ( $block_type ) {
			WP_CLI::success( "Block type '{$args[0]}' is registered." );
		} else {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Converts a WP_Block_Type object to an associative array.
	 *
	 * @param WP_Block_Type $block_type Block type object.
	 * @return array
	 */
	private function block_type_to_array( $block_type ) {
		return [
			'name'                   => $block_type->name,
			'title'                  => $this->get_block_type_property( $block_type, 'title' ),
			'description'            => $this->get_block_type_property( $block_type, 'description' ),
			'category'               => $this->get_block_type_property( $block_type, 'category' ),
			'is_dynamic'             => $block_type->is_dynamic(),
			'icon'                   => $this->get_block_type_property( $block_type, 'icon' ),
			'keywords'               => $this->get_block_type_property( $block_type, 'keywords' ),
			'parent'                 => $this->get_block_type_property( $block_type, 'parent' ),
			'ancestor'               => $this->get_block_type_property( $block_type, 'ancestor' ),
			'allowed_blocks'         => $this->get_block_type_property( $block_type, 'allowed_blocks' ),
			'supports'               => $this->get_block_type_property( $block_type, 'supports' ),
			'attributes'             => $this->get_block_type_property( $block_type, 'attributes' ),
			'provides_context'       => $this->get_block_type_property( $block_type, 'provides_context' ),
			'uses_context'           => $this->get_block_type_property( $block_type, 'uses_context' ),
			'block_hooks'            => $this->get_block_type_property( $block_type, 'block_hooks' ),
			'selectors'              => $this->get_block_type_property( $block_type, 'selectors' ),
			'styles'                 => $this->get_block_type_property( $block_type, 'styles' ),
			'example'                => $this->get_block_type_property( $block_type, 'example' ),
			'editor_script_handles'  => $this->get_block_type_property( $block_type, 'editor_script_handles' ),
			'script_handles'         => $this->get_block_type_property( $block_type, 'script_handles' ),
			'view_script_handles'    => $this->get_block_type_property( $block_type, 'view_script_handles' ),
			'view_script_module_ids' => $this->get_block_type_property( $block_type, 'view_script_module_ids' ),
			'editor_style_handles'   => $this->get_block_type_property( $block_type, 'editor_style_handles' ),
			'style_handles'          => $this->get_block_type_property( $block_type, 'style_handles' ),
			'view_style_handles'     => $this->get_block_type_property( $block_type, 'view_style_handles' ),
			'api_version'            => $this->get_block_type_property( $block_type, 'api_version' ),
		];
	}

	/**
	 * Safely gets a property from a WP_Block_Type object.
	 *
	 * Some properties may not exist on all block types depending on WordPress
	 * version or how the block was registered.
	 *
	 * @param WP_Block_Type $block_type Block type object.
	 * @param string        $property   Property name.
	 * @return mixed|null Property value or null if not set.
	 */
	private function get_block_type_property( $block_type, $property ) {
		return isset( $block_type->$property ) ? $block_type->$property : null;
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block-type' );
	}
}
