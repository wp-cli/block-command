<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Retrieves details on block templates and template parts.
 *
 * Get information on block templates used in Full Site Editing (FSE) themes.
 *
 * ## EXAMPLES
 *
 *     # List all templates
 *     $ wp block template list
 *
 *     # List template parts for the header area
 *     $ wp block template list --type=wp_template_part --area=header
 *
 *     # Get a specific template
 *     $ wp block template get twentytwentyfour//single
 *
 * @package wp-cli
 */
class Block_Template_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'id',
		'slug',
		'title',
		'source',
		'type',
	];

	/**
	 * Lists block templates or template parts.
	 *
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : Template type.
	 * ---
	 * default: wp_template
	 * options:
	 *   - wp_template
	 *   - wp_template_part
	 * ---
	 *
	 * [--slug=<slug>]
	 * : Filter by template slug(s). Accepts a single slug or comma-separated list.
	 *
	 * [--area=<area>]
	 * : For template parts, filter by area (header, footer, sidebar, uncategorized).
	 *
	 * [--post-type=<post-type>]
	 * : Filter templates by post type they apply to.
	 *
	 * [--source=<source>]
	 * : Filter by source (theme, plugin, custom).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each template.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific template fields.
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
	 * These fields will be displayed by default for each template:
	 *
	 * * id
	 * * slug
	 * * title
	 * * source
	 * * type
	 *
	 * These fields are optionally available:
	 *
	 * * theme
	 * * description
	 * * status
	 * * origin
	 * * is_custom
	 * * has_theme_file
	 * * author
	 * * area (template parts only)
	 * * content
	 *
	 * ## EXAMPLES
	 *
	 *     # List all templates
	 *     $ wp block template list
	 *
	 *     # List template parts for the header area
	 *     $ wp block template list --type=wp_template_part --area=header
	 *
	 *     # List templates from the theme
	 *     $ wp block template list --source=theme
	 *
	 *     # List specific templates by slug
	 *     $ wp block template list --slug=single,archive
	 *
	 *     # List templates for a specific post type
	 *     $ wp block template list --post-type=page
	 *
	 *     # Export templates as JSON
	 *     $ wp block template list --format=json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$type = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'wp_template';
		unset( $assoc_args['type'] );

		$query = [];

		// Filter by slug(s).
		if ( ! empty( $assoc_args['slug'] ) ) {
			$slugs             = array_map( 'trim', explode( ',', $assoc_args['slug'] ) );
			$query['slug__in'] = $slugs;
			unset( $assoc_args['slug'] );
		}

		// Filter by area for template parts.
		if ( ! empty( $assoc_args['area'] ) ) {
			$query['area'] = $assoc_args['area'];
			unset( $assoc_args['area'] );
		}

		// Filter by post type.
		if ( ! empty( $assoc_args['post-type'] ) ) {
			$query['post_type'] = $assoc_args['post-type'];
			unset( $assoc_args['post-type'] );
		}

		$templates = get_block_templates( $query, $type );

		// Filter by source.
		if ( ! empty( $assoc_args['source'] ) ) {
			$source    = $assoc_args['source'];
			$templates = array_filter(
				$templates,
				function ( $template ) use ( $source ) {
					return $template->source === $source;
				}
			);
			unset( $assoc_args['source'] );
		}

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = wp_list_pluck( $templates, 'id' );
			echo implode( ' ', $ids );
			return;
		}

		$items = array_map( [ $this, 'template_to_array' ], $templates );

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a block template.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Template ID in format 'theme//slug' (e.g., 'twentytwentyfour//single').
	 *
	 * [--type=<type>]
	 * : Template type.
	 * ---
	 * default: wp_template
	 * options:
	 *   - wp_template
	 *   - wp_template_part
	 * ---
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole template, returns the value of a single field.
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
	 *     # Get the single post template
	 *     $ wp block template get twentytwentyfour//single
	 *
	 *     # Get template content only
	 *     $ wp block template get twentytwentyfour//single --field=content
	 *
	 *     # Get as JSON
	 *     $ wp block template get twentytwentyfour//single --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$type     = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'wp_template';
		$template = get_block_template( $args[0], $type );

		if ( ! $template ) {
			WP_CLI::error( "Block template '{$args[0]}' not found." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_merge(
				$this->fields,
				[
					'theme',
					'description',
					'status',
					'origin',
					'is_custom',
					'has_theme_file',
					'author',
					'area',
					'content',
				]
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->template_to_array( $template );

		$formatter->display_item( $data );
	}

	/**
	 * Exports a block template to a file.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Template ID to export.
	 *
	 * [--type=<type>]
	 * : Template type.
	 * ---
	 * default: wp_template
	 * options:
	 *   - wp_template
	 *   - wp_template_part
	 * ---
	 *
	 * [--file=<file>]
	 * : File path to export to. Parent directories will be created if needed.
	 *
	 * [--dir=<directory>]
	 * : Directory to export to. Defaults to current directory. Creates directory if needed.
	 *
	 * [--stdout]
	 * : Output to stdout instead of file.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export template to file
	 *     $ wp block template export twentytwentyfour//single
	 *
	 *     # Export to stdout
	 *     $ wp block template export twentytwentyfour//single --stdout
	 *
	 *     # Export to specific directory
	 *     $ wp block template export twentytwentyfour//single --dir=./templates/
	 *
	 *     # Export to specific file path
	 *     $ wp block template export twentytwentyfour//single --file=exports/templates/single.html
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function export( $args, $assoc_args ) {
		// Check for mutually exclusive options.
		if ( ! empty( $assoc_args['file'] ) && ! empty( $assoc_args['dir'] ) ) {
			WP_CLI::error( 'The --file and --dir options are mutually exclusive.' );
		}

		$type     = isset( $assoc_args['type'] ) ? $assoc_args['type'] : 'wp_template';
		$template = get_block_template( $args[0], $type );

		if ( ! $template ) {
			WP_CLI::error( "Block template '{$args[0]}' not found." );
		}

		$content = $template->content;

		if ( isset( $assoc_args['stdout'] ) ) {
			echo $content;
			return;
		}

		// Handle --file option for direct file path.
		if ( ! empty( $assoc_args['file'] ) ) {
			$filepath = $assoc_args['file'];
			$dir      = dirname( $filepath );

			// Create parent directories if needed.
			if ( ! empty( $dir ) && '.' !== $dir && ! is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					WP_CLI::error( "Could not create directory '{$dir}'." );
				}
			}
		} else {
			$dir = isset( $assoc_args['dir'] ) ? rtrim( $assoc_args['dir'], '/' ) : '.';

			// Create directory if needed.
			if ( ! is_dir( $dir ) ) {
				if ( ! wp_mkdir_p( $dir ) ) {
					WP_CLI::error( "Could not create directory '{$dir}'." );
				}
			}

			$filename = $template->slug . '.html';
			$filepath = $dir . '/' . $filename;
		}

		$result = file_put_contents( $filepath, $content );

		if ( false === $result ) {
			WP_CLI::error( "Failed to write to '{$filepath}'." );
		}

		WP_CLI::success( "Exported template to '{$filepath}'." );
	}

	/**
	 * Converts a template object to a standardized associative array.
	 *
	 * @param WP_Block_Template $template Template object.
	 * @return array
	 */
	private function template_to_array( $template ) {
		return [
			'id'             => $template->id,
			'slug'           => $template->slug,
			'theme'          => $template->theme,
			'type'           => $template->type,
			'source'         => $template->source,
			'origin'         => $template->origin,
			'title'          => is_array( $template->title ) ? $template->title['rendered'] : $template->title,
			'description'    => $template->description,
			'status'         => $template->status,
			'author'         => $template->author,
			'is_custom'      => $template->is_custom,
			'has_theme_file' => $template->has_theme_file,
			'area'           => isset( $template->area ) ? $template->area : '',
			'content'        => $template->content,
		];
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block-template' );
	}
}
