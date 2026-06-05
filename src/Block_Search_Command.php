<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI_Command;

/**
 * Searches posts for block usage.
 *
 * Returns matching posts where the requested block appears anywhere in the
 * parsed block tree, including nested blocks.
 *
 * ## OPTIONS
 *
 * [--block=<block-name>]
 * : Block type name to search for (for example, 'core/paragraph').
 *
 * [--block-namespace=<block-namespace>]
 * : Limit matches to blocks within a specific namespace (for example, 'core').
 *
 * [--style=<style-name>]
 * : Limit matches to blocks using a specific block style.
 *
 * [--pattern=<pattern-name>]
 * : Limit matches to blocks embedded from a specific pattern (for example, 'twentytwentyfive/event-rsvp').
 *
 * [--pattern-namespace=<pattern-namespace>]
 * : Limit matches to blocks embedded from patterns within a specific namespace (for example, 'twentytwentyfive').
 *
 * [--synced-pattern=<post-id>]
 * : Limit matches to reusable block references for a specific synced pattern post ID.
 *
 * [--<field>=<value>]
 * : One or more args to pass to WP_Query.
 *
 * [--field=<field>]
 * : Prints the value of a single field for each matching post.
 *
 * [--fields=<fields>]
 * : Limit the output to specific result fields.
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
 * These fields will be displayed by default for each matching post:
 *
 * * ID
 * * post_title
 * * post_name
 * * post_date
 * * post_status
 *
 * These fields are optionally available:
 *
 * * post_type
 * * url
 * * occurrences
 *
 * ## EXAMPLES
 *
 *     # Find posts using the paragraph block.
 *     $ wp block search --block=core/paragraph
 *
 *     # Find any post type posts using the heading block.
 *     $ wp block search --block=core/heading --post_type=any
 *
 *     # Find posts using any block in namespace like 'core'.
 *     $ wp block search --block-namespace=core
 *
 *     # Find posts using any blocks with block style of 'rounded'.
 *     $ wp block search --style=rounded
 *
 *     # Find posts using certain block with a specific style.
 *     $ wp block search --block=core/image --style=rounded
 *
 *     # Search a namespace with a specific style.
 *     $ wp block search --block-namespace=core --style=rounded
 *
 *     # Search published pages for rounded images.
 *     $ wp block search --block=core/image --style=rounded --post_type=page --post_status=publish
 *
 *     # Find posts using blocks embedded from a specific pattern.
 *     $ wp block search --pattern=twentytwentyfive/event-rsvp
 *
 *     # Find posts using blocks from patterns in a specific namespace.
 *     $ wp block search --pattern-namespace=twentytwentyfive
 *
 *     # Find posts using a specific synced pattern.
 *     $ wp block search --synced-pattern=123
 *
 *     # Show selected fields as JSON for further processing.
 *     $ wp block search --block=core/heading --post_status=publish --fields=ID,post_type,occurrences --format=json
 *
 *     # Restrict search to specific posts and return the count.
 *     $ wp block search --style=rounded --post__in=21,42,84 --format=count
 *
 *     # Return only matching post IDs.
 *     $ wp block search --block=core/paragraph --format=ids
 *
 *     # Return count of matching posts.
 *     $ wp block search --block=core/heading --format=count
 *
 * @package wp-cli
 */
class Block_Search_Command extends WP_CLI_Command {

	/**
	 * Searches posts for block usage.
	 *
	 * @param array $args Positional arguments. Unused.
	 * @param array $assoc_args Associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) {
		$block_name      = Utils\get_flag_value( $assoc_args, 'block', null );
		$block_namespace = Utils\get_flag_value( $assoc_args, 'block-namespace', null );
		$style_name      = Utils\get_flag_value( $assoc_args, 'style', '' );
		$pattern_name    = Utils\get_flag_value( $assoc_args, 'pattern', null );
		$pattern_ns      = Utils\get_flag_value( $assoc_args, 'pattern-namespace', null );
		$synced_pattern  = Utils\get_flag_value( $assoc_args, 'synced-pattern', null );

		if ( null !== $synced_pattern && '' !== $synced_pattern ) {
			$synced_pattern = (int) $synced_pattern;

			if ( $synced_pattern <= 0 ) {
				WP_CLI::error( 'The --synced-pattern parameter must be a positive integer post ID.' );
			}
		}

		if ( ( null === $block_name || '' === $block_name ) && ( null === $block_namespace || '' === $block_namespace ) && '' === $style_name && ( null === $pattern_name || '' === $pattern_name ) && ( null === $pattern_ns || '' === $pattern_ns ) && ( null === $synced_pattern || '' === $synced_pattern ) ) {
			WP_CLI::error( 'At least one block filter is required: --block, --block-namespace, --style, --pattern, --pattern-namespace, or --synced-pattern.' );
		}

		if ( null !== $block_name && '' !== $block_name && null !== $block_namespace && '' !== $block_namespace ) {
			WP_CLI::error( 'The --block and --block-namespace parameters are mutually exclusive.' );
		}

		if ( null !== $pattern_name && '' !== $pattern_name && null !== $pattern_ns && '' !== $pattern_ns ) {
			WP_CLI::error( 'The --pattern and --pattern-namespace parameters are mutually exclusive.' );
		}

		$defaults = [
			'post_type'              => 'any',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		$array_arguments  = [ 'date_query', 'tax_query', 'meta_query' ];
		$query_assoc_args = Utils\parse_shell_arrays( $assoc_args, $array_arguments );

		unset(
			$query_assoc_args['block'],
			$query_assoc_args['block-namespace'],
			$query_assoc_args['style'],
			$query_assoc_args['pattern'],
			$query_assoc_args['pattern-namespace'],
			$query_assoc_args['synced-pattern'],
			$query_assoc_args['field'],
			$query_assoc_args['fields'],
			$query_assoc_args['format']
		);

		$query_args = array_merge( $defaults, $query_assoc_args );
		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		if ( isset( $query_args['post_type'] ) && 'any' !== $query_args['post_type'] ) {
			$query_args['post_type'] = explode( ',', $query_args['post_type'] );
		}

		$results = [];
		$query   = new \WP_Query( $query_args );

		foreach ( $query->posts as $post ) {
			if ( $this->can_use_has_block_fast_path( $block_name, $block_namespace, $style_name, $pattern_name, $pattern_ns, $synced_pattern ) && ! has_block( $block_name, $post ) ) {
				continue;
			}

			$matches = $this->find_matching_blocks( $post->post_content, $block_name, $block_namespace, $style_name, $pattern_name, $pattern_ns, $synced_pattern );

			if ( empty( $matches ) ) {
				continue;
			}

			$results[] = [
				'ID'          => $post->ID,
				'post_title'  => $post->post_title,
				'post_name'   => $post->post_name,
				'post_date'   => $post->post_date,
				'post_type'   => $post->post_type,
				'post_status' => $post->post_status,
				'url'         => get_permalink( $post->ID ),
				'occurrences' => count( $matches ),
			];
		}

		$formatter = new Formatter(
			$assoc_args,
			[ 'ID', 'post_title', 'post_name', 'post_date', 'post_status' ],
			'post'
		);

		if ( 'ids' === $formatter->format ) {
			echo implode( ' ', wp_list_pluck( $results, 'ID' ) );
			return;
		}

		if ( 'count' === $formatter->format ) {
			WP_CLI::line( (string) count( $results ) );
			return;
		}

		$formatter->display_items( $results );
	}

	/**
	 * Converts known CSV query args to arrays for WP_Query compatibility.
	 *
	 * @param array $assoc_args Query args.
	 * @return array
	 */
	private static function process_csv_arguments_to_arrays( array $assoc_args ) {
		$int_array_fields = [
			'post__in',
			'post__not_in',
			'post_parent__in',
			'post_parent__not_in',
			'author__in',
			'author__not_in',
			'category__in',
			'category__not_in',
			'category__and',
			'tag__in',
			'tag__not_in',
			'tag__and',
		];

		$string_array_fields = [
			'post_name__in',
			'tag_slug__in',
		];

		foreach ( $int_array_fields as $field ) {
			if ( isset( $assoc_args[ $field ] ) && ! is_array( $assoc_args[ $field ] ) ) {
				$assoc_args[ $field ] = array_map( 'intval', explode( ',', (string) $assoc_args[ $field ] ) );
			}
		}

		foreach ( $string_array_fields as $field ) {
			if ( isset( $assoc_args[ $field ] ) && ! is_array( $assoc_args[ $field ] ) ) {
				$assoc_args[ $field ] = array_map( 'trim', explode( ',', (string) $assoc_args[ $field ] ) );
			}
		}

		return $assoc_args;
	}

	/**
	 * Finds matching blocks in post content.
	 *
	 * @param string      $content Post content.
	 * @param string|null $block_name Requested block name.
	 * @param string|null $block_namespace Requested block namespace.
	 * @param string      $style_name Requested style name.
	 * @param string|null $pattern_name Requested exact pattern name.
	 * @param string|null $pattern_namespace Requested pattern namespace.
	 * @param int|null    $synced_pattern Requested synced pattern post ID.
	 * @return array
	 */
	private function find_matching_blocks( $content, $block_name, $block_namespace, $style_name, $pattern_name, $pattern_namespace, $synced_pattern ) {
		$blocks  = parse_blocks( $content );
		$matches = [];

		foreach ( $this->flatten_blocks( $blocks ) as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( ! $this->matches_block_filter( $block['blockName'], $block_name, $block_namespace ) ) {
				continue;
			}

			if ( ! $this->matches_pattern_filter( $block, $pattern_name, $pattern_namespace ) ) {
				continue;
			}

			if ( ! $this->matches_synced_pattern_filter( $block, $synced_pattern ) ) {
				continue;
			}

			if ( ! $this->matches_style_filter( $block, $style_name ) ) {
				continue;
			}

			$matches[] = $block;
		}

		return $matches;
	}

	/**
	 * Checks whether an exact block-only search can use has_block() as a fast precheck.
	 *
	 * @param string|null $block_name Requested exact block name.
	 * @param string|null $block_namespace Requested block namespace.
	 * @param string      $style_name Requested style name.
	 * @param string|null $pattern_name Requested exact pattern name.
	 * @param string|null $pattern_namespace Requested pattern namespace.
	 * @param int|null    $synced_pattern Requested synced pattern post ID.
	 * @return bool
	 */
	private function can_use_has_block_fast_path( $block_name, $block_namespace, $style_name, $pattern_name, $pattern_namespace, $synced_pattern ) {
		return null !== $block_name
			&& '' !== $block_name
			&& ( null === $block_namespace || '' === $block_namespace )
			&& '' === $style_name
			&& ( null === $pattern_name || '' === $pattern_name )
			&& ( null === $pattern_namespace || '' === $pattern_namespace )
			&& null === $synced_pattern;
	}

	/**
	 * Checks whether a parsed block matches the requested block filter.
	 *
	 * @param string      $candidate_block_name Parsed block name.
	 * @param string|null $block_name Requested exact block name.
	 * @param string|null $block_namespace Requested namespace.
	 * @return bool
	 */
	private function matches_block_filter( $candidate_block_name, $block_name, $block_namespace ) {
		if ( null !== $block_name && '' !== $block_name ) {
			return $candidate_block_name === $block_name;
		}

		if ( null !== $block_namespace && '' !== $block_namespace ) {
			return 0 === strpos( $candidate_block_name, $block_namespace . '/' );
		}

		return true;
	}

	/**
	 * Checks whether a parsed block matches the requested pattern filter.
	 *
	 * @param array       $block Parsed block.
	 * @param string|null $pattern_name Requested exact pattern name.
	 * @param string|null $pattern_namespace Requested pattern namespace.
	 * @return bool
	 */
	private function matches_pattern_filter( array $block, $pattern_name, $pattern_namespace ) {
		if ( ( null === $pattern_name || '' === $pattern_name ) && ( null === $pattern_namespace || '' === $pattern_namespace ) ) {
			return true;
		}

		$ancestor_pattern_name = $this->get_ancestor_pattern_name( $block );

		if ( '' === $ancestor_pattern_name ) {
			return false;
		}

		if ( null !== $pattern_name && '' !== $pattern_name ) {
			return $ancestor_pattern_name === $pattern_name;
		}

		return 0 === strpos( $ancestor_pattern_name, $pattern_namespace . '/' );
	}

	/**
	 * Checks whether a parsed block matches the requested synced pattern filter.
	 *
	 * @param array    $block Parsed block.
	 * @param int|null $synced_pattern Requested synced pattern post ID.
	 * @return bool
	 */
	private function matches_synced_pattern_filter( array $block, $synced_pattern ) {
		if ( null === $synced_pattern ) {
			return true;
		}

		if ( 'core/block' !== $block['blockName'] ) {
			return false;
		}

		if ( ! isset( $block['attrs']['ref'] ) ) {
			return false;
		}

		return (int) $block['attrs']['ref'] === $synced_pattern;
	}

	/**
	 * Gets the nearest pattern name from the block or its inherited ancestor context.
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	private function get_ancestor_pattern_name( array $block ) {
		if ( isset( $block['attrs']['metadata']['patternName'] ) && is_string( $block['attrs']['metadata']['patternName'] ) ) {
			return $block['attrs']['metadata']['patternName'];
		}

		if ( isset( $block['ancestorPatternName'] ) && is_string( $block['ancestorPatternName'] ) ) {
			return $block['ancestorPatternName'];
		}

		return '';
	}

	/**
	 * Flattens nested block arrays.
	 *
	 * @param array  $blocks Parsed blocks.
	 * @param string $ancestor_pattern_name Pattern name inherited from the nearest parent wrapper.
	 * @return array
	 */
	private function flatten_blocks( array $blocks, $ancestor_pattern_name = '' ) {
		$flat_blocks = [];

		foreach ( $blocks as $block ) {
			$block_pattern_name = $this->get_ancestor_pattern_name( $block );

			if ( '' === $block_pattern_name ) {
				$block_pattern_name = $ancestor_pattern_name;
			}

			if ( '' !== $block_pattern_name ) {
				$block['ancestorPatternName'] = $block_pattern_name;
			}

			$flat_blocks[] = $block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				$flat_blocks = array_merge( $flat_blocks, $this->flatten_blocks( $block['innerBlocks'], $block_pattern_name ) );
			}
		}

		return $flat_blocks;
	}

	/**
	 * Checks whether a parsed block matches the requested style filter.
	 *
	 * @param array  $block Parsed block.
	 * @param string $style_name Requested style name.
	 * @return bool
	 */
	private function matches_style_filter( array $block, $style_name ) {
		if ( '' === $style_name ) {
			return true;
		}

		$class_name = '';

		if ( isset( $block['attrs']['className'] ) && is_string( $block['attrs']['className'] ) ) {
			$class_name = $block['attrs']['className'];
		}

		if ( false !== strpos( $class_name, 'is-style-' . $style_name ) ) {
			return true;
		}

		return ! empty( $block['innerHTML'] ) && false !== strpos( $block['innerHTML'], 'is-style-' . $style_name );
	}
}
