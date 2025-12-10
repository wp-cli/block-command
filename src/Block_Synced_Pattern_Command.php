<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI_Command;
use WP_Post;

/**
 * Manages synced patterns (reusable blocks).
 *
 * Synced patterns are stored as the 'wp_block' post type and can be either
 * synced (changes reflect everywhere) or not synced (regular patterns).
 *
 * ## EXAMPLES
 *
 *     # List all synced patterns
 *     $ wp block synced-pattern list
 *
 *     # Create a synced pattern
 *     $ wp block synced-pattern create --title="My Pattern" --content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'
 *
 *     # Delete a synced pattern
 *     $ wp block synced-pattern delete 123
 *
 * @package wp-cli
 */
class Block_Synced_Pattern_Command extends WP_CLI_Command {

	/**
	 * Default fields to display.
	 *
	 * @var array
	 */
	private $fields = [
		'ID',
		'post_title',
		'post_name',
		'sync_status',
		'post_date',
	];

	/**
	 * Lists synced patterns.
	 *
	 * ## OPTIONS
	 *
	 * [--sync-status=<status>]
	 * : Filter by sync status.
	 * ---
	 * default: all
	 * options:
	 *   - synced
	 *   - unsynced
	 *   - all
	 * ---
	 *
	 * [--search=<search>]
	 * : Search by title.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each pattern.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
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
	 * * ID
	 * * post_title
	 * * post_name
	 * * sync_status
	 * * post_date
	 *
	 * These fields are optionally available:
	 *
	 * * post_content
	 * * post_status
	 * * post_author
	 *
	 * ## EXAMPLES
	 *
	 *     # List all synced patterns
	 *     $ wp block synced-pattern list --sync-status=synced
	 *
	 *     # Search for patterns by title
	 *     $ wp block synced-pattern list --search=hero
	 *
	 *     # Export all synced patterns to JSON
	 *     $ wp block synced-pattern list --format=json > synced-patterns.json
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$query_args = [
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		// Search by title.
		if ( ! empty( $assoc_args['search'] ) ) {
			$query_args['s'] = $assoc_args['search'];
			unset( $assoc_args['search'] );
		}

		// Filter by sync status.
		$sync_status = isset( $assoc_args['sync-status'] ) ? $assoc_args['sync-status'] : 'all';
		unset( $assoc_args['sync-status'] );

		if ( 'synced' === $sync_status ) {
			$query_args['meta_query'] = [
				'relation' => 'OR',
				[
					'key'     => 'wp_pattern_sync_status',
					'compare' => 'NOT EXISTS',
				],
				[
					'key'     => 'wp_pattern_sync_status',
					'value'   => '',
					'compare' => '=',
				],
			];
		} elseif ( 'unsynced' === $sync_status ) {
			$query_args['meta_query'] = [
				[
					'key'     => 'wp_pattern_sync_status',
					'value'   => 'unsynced',
					'compare' => '=',
				],
			];
		}

		$patterns = get_posts( $query_args );

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = wp_list_pluck( $patterns, 'ID' );
			echo implode( ' ', $ids );
			return;
		}

		$items = array_map( [ $this, 'pattern_to_array' ], $patterns );

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a synced pattern.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The synced pattern ID.
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
	 *     # Get a synced pattern
	 *     $ wp block synced-pattern get 123
	 *
	 *     # Get pattern content only
	 *     $ wp block synced-pattern get 123 --field=post_content
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$pattern = get_post( $args[0] );

		if ( ! $pattern || 'wp_block' !== $pattern->post_type ) {
			WP_CLI::error( "Synced pattern with ID {$args[0]} not found." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_merge(
				$this->fields,
				[
					'post_content',
					'post_status',
					'post_author',
				]
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$data      = $this->pattern_to_array( $pattern );

		$formatter->display_item( $data );
	}

	/**
	 * Creates a synced pattern.
	 *
	 * ## OPTIONS
	 *
	 * [--title=<title>]
	 * : The pattern title.
	 *
	 * [--slug=<slug>]
	 * : The pattern slug. Default: sanitized title.
	 *
	 * [--content=<content>]
	 * : The block content.
	 *
	 * [--sync-status=<status>]
	 * : Sync status.
	 * ---
	 * default: synced
	 * options:
	 *   - synced
	 *   - unsynced
	 * ---
	 *
	 * [--status=<status>]
	 * : Post status.
	 * ---
	 * default: publish
	 * ---
	 *
	 * [<file>]
	 * : Read content from file. Pass '-' for STDIN.
	 *
	 * [--porcelain]
	 * : Output only the new pattern ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a synced pattern from content
	 *     $ wp block synced-pattern create --title="My Hero" --content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'
	 *
	 *     # Create from file
	 *     $ wp block synced-pattern create --title="Header" header.html
	 *
	 *     # Create an unsynced pattern
	 *     $ wp block synced-pattern create --title="Footer" --sync-status=unsynced footer.html
	 *
	 *     # Create from STDIN
	 *     $ cat content.html | wp block synced-pattern create --title="From STDIN" -
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function create( $args, $assoc_args ) {
		$content = '';

		// Read content from file or STDIN.
		if ( ! empty( $args[0] ) ) {
			$content = $this->read_from_file_or_stdin( $args[0] );
		} elseif ( ! empty( $assoc_args['content'] ) ) {
			$content = $assoc_args['content'];
		}

		if ( empty( $assoc_args['title'] ) ) {
			WP_CLI::error( 'Pattern title is required. Use --title=<title>.' );
		}

		if ( empty( $content ) ) {
			WP_CLI::error( 'Pattern content is required. Use --content=<content> or provide a file.' );
		}

		// Warn if content doesn't appear to contain valid blocks.
		$this->validate_block_content( $content );

		$post_data = [
			'post_type'    => 'wp_block',
			'post_title'   => $assoc_args['title'],
			'post_content' => $content,
			'post_status'  => isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'publish',
		];

		if ( ! empty( $assoc_args['slug'] ) ) {
			$post_data['post_name'] = $assoc_args['slug'];
		}

		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			WP_CLI::error( $post_id->get_error_message() );
		}

		// Set sync status meta.
		$sync_status = isset( $assoc_args['sync-status'] ) ? $assoc_args['sync-status'] : 'synced';
		if ( 'unsynced' === $sync_status ) {
			update_post_meta( $post_id, 'wp_pattern_sync_status', 'unsynced' );
		}

		if ( isset( $assoc_args['porcelain'] ) ) {
			WP_CLI::line( (string) $post_id );
		} else {
			$status_label = 'synced' === $sync_status ? 'synced' : 'unsynced';
			WP_CLI::success( "Created {$status_label} pattern {$post_id}." );
		}
	}

	/**
	 * Updates a synced pattern.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The synced pattern ID.
	 *
	 * [--title=<title>]
	 * : The pattern title.
	 *
	 * [--content=<content>]
	 * : The block content.
	 *
	 * [--sync-status=<status>]
	 * : Sync status.
	 * ---
	 * options:
	 *   - synced
	 *   - unsynced
	 * ---
	 *
	 * [<file>]
	 * : Read content from file. Pass '-' for STDIN.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update pattern title
	 *     $ wp block synced-pattern update 123 --title="Updated Hero"
	 *
	 *     # Update content from file
	 *     $ wp block synced-pattern update 123 updated-content.html
	 *
	 *     # Change sync status
	 *     $ wp block synced-pattern update 123 --sync-status=unsynced
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function update( $args, $assoc_args ) {
		$pattern_id = $args[0];
		$pattern    = get_post( $pattern_id );

		if ( ! $pattern || 'wp_block' !== $pattern->post_type ) {
			WP_CLI::error( "Synced pattern with ID {$pattern_id} not found." );
		}

		$post_data = [
			'ID' => $pattern_id,
		];

		// Update title.
		if ( ! empty( $assoc_args['title'] ) ) {
			$post_data['post_title'] = $assoc_args['title'];
		}

		// Read content from file or option.
		if ( ! empty( $args[1] ) ) {
			$post_data['post_content'] = $this->read_from_file_or_stdin( $args[1] );
		} elseif ( ! empty( $assoc_args['content'] ) ) {
			$post_data['post_content'] = $assoc_args['content'];
		}

		if ( count( $post_data ) > 1 ) {
			$result = wp_update_post( $post_data, true );

			if ( is_wp_error( $result ) ) {
				WP_CLI::error( $result->get_error_message() );
			}
		}

		// Update sync status meta.
		if ( isset( $assoc_args['sync-status'] ) ) {
			if ( 'unsynced' === $assoc_args['sync-status'] ) {
				update_post_meta( $pattern_id, 'wp_pattern_sync_status', 'unsynced' );
			} else {
				delete_post_meta( $pattern_id, 'wp_pattern_sync_status' );
			}
		}

		WP_CLI::success( "Updated synced pattern {$pattern_id}." );
	}

	/**
	 * Deletes one or more synced patterns.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more synced pattern IDs.
	 *
	 * [--force]
	 * : Skip trash and permanently delete.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a synced pattern (to trash)
	 *     $ wp block synced-pattern delete 123
	 *
	 *     # Permanently delete
	 *     $ wp block synced-pattern delete 123 --force
	 *
	 *     # Delete multiple
	 *     $ wp block synced-pattern delete 123 456 789
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function delete( $args, $assoc_args ) {
		$force = Utils\get_flag_value( $assoc_args, 'force', false );

		$count   = 0;
		$errored = 0;

		foreach ( $args as $pattern_id ) {
			$pattern = get_post( $pattern_id );

			if ( ! $pattern || 'wp_block' !== $pattern->post_type ) {
				WP_CLI::warning( "Synced pattern with ID {$pattern_id} not found." );
				++$errored;
				continue;
			}

			$result = wp_delete_post( $pattern_id, $force );

			if ( $result ) {
				++$count;
			} else {
				WP_CLI::warning( "Failed to delete synced pattern {$pattern_id}." );
				++$errored;
			}
		}

		if ( $count > 0 ) {
			$action = $force ? 'Deleted' : 'Trashed';
			WP_CLI::success( "{$action} {$count} synced pattern(s)." );
		}

		if ( $errored > 0 ) {
			WP_CLI::error( "Failed to delete {$errored} synced pattern(s)." );
		}
	}

	/**
	 * Converts a pattern post to a standardized associative array.
	 *
	 * @param WP_Post $pattern Pattern post object.
	 * @return array
	 */
	private function pattern_to_array( $pattern ) {
		$sync_status = get_post_meta( $pattern->ID, 'wp_pattern_sync_status', true );

		return [
			'ID'           => $pattern->ID,
			'post_title'   => $pattern->post_title,
			'post_name'    => $pattern->post_name,
			'post_content' => $pattern->post_content,
			'post_status'  => $pattern->post_status,
			'post_author'  => $pattern->post_author,
			'post_date'    => $pattern->post_date,
			'sync_status'  => 'unsynced' === $sync_status ? 'unsynced' : 'synced',
		];
	}

	/**
	 * Reads content from a file or STDIN.
	 *
	 * @param string $file File path or '-' for STDIN.
	 * @return string File content.
	 */
	private function read_from_file_or_stdin( $file ) {
		if ( '-' === $file ) {
			$content = file_get_contents( 'php://stdin' );
			if ( false === $content ) {
				WP_CLI::error( 'Failed to read from STDIN.' );
			}
			return $content;
		}

		if ( ! file_exists( $file ) ) {
			WP_CLI::error( "File '{$file}' does not exist." );
		}

		$content = file_get_contents( $file );
		if ( false === $content ) {
			WP_CLI::error( "Failed to read file '{$file}'." );
		}
		return $content;
	}

	/**
	 * Gets the formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'synced-pattern' );
	}

	/**
	 * Validates block content and warns if it appears malformed.
	 *
	 * @param string $content Block content to validate.
	 */
	private function validate_block_content( $content ) {
		$blocks = parse_blocks( $content );

		// Filter out empty/null blocks (freeform content).
		$valid_blocks = array_filter(
			$blocks,
			function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		);

		if ( empty( $valid_blocks ) ) {
			WP_CLI::warning( 'Content does not appear to contain valid blocks. The pattern will be created with the provided content.' );
		}
	}
}
