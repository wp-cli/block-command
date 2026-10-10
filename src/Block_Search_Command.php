<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI\Utils;
use WP_CLI_Command;

/**
 * Searches posts for block usage.
 *
 * @package wp-cli
 */
class Block_Search_Command extends WP_CLI_Command {

	/**
	 * Number of candidate posts loaded per batch.
	 */
	const BATCH_SIZE = 200;

	/**
	 * Whether the database supports REGEXP, or null if not yet checked.
	 *
	 * @var bool|null
	 */
	private $regexp_supported = null;

	/**
	 * Searches posts for block usage.
	 *
	 * Returns matching posts where the requested block appears anywhere in the
	 * parsed block tree, including nested blocks.
	 *
	 * To reduce unnecessary parsing on large datasets, the command first applies a
	 * coarse `post_content` prefilter when a safe marker is available, then
	 * confirms matches by parsing blocks. Candidate posts are loaded in batches to
	 * keep memory usage low. A plain `--block` search skips parsing entirely unless
	 * the `occurrences` field is requested.
	 *
	 * At least one search filter is required: `--block`, `--block-namespace`,
	 * `--style`, `--pattern`, `--pattern-namespace`, or `--synced-pattern`.
	 *
	 * Pattern filters match only blocks whose own `metadata.patternName` matches.
	 *
	 * The `--synced-pattern` filter matches reusable block references by synced
	 * pattern post ID, and cannot be combined with `--block` or `--block-namespace`.
	 *
	 * ## OPTIONS
	 *
	 * [--block=<block-name>]
	 * : Block type name to search for (for example, 'core/paragraph'). A name without a namespace is treated as a core block.
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
	 * [--limit=<limit>]
	 * : Stop after this many matching posts, in query order (post ID ascending unless
	 *   `--orderby` is given). Unlike `--posts_per_page`, which limits the candidate
	 *   posts examined, this counts matches. Cannot be combined with `--paged`.
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Query. The default `post_type` is `any`,
	 *   which excludes `wp_block` and other post types that are not searchable.
	 *   Pass `--post_type=wp_block` to search them explicitly.
	 *
	 *   Results are complete by default; candidate posts are processed in batches
	 *   internally. Pass `--posts_per_page=<n>` and `--paged=<n>` to search only
	 *   that window of candidate posts.
	 *
	 *   Content inside synced patterns is not searched through `core/block`
	 *   references, so a block that only exists inside a synced pattern is not
	 *   found in the posts that use that pattern.
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
	 *     # Limit the candidate posts scanned with a native query argument.
	 *     $ wp block search --block=core/paragraph --showposts=50 --format=ids
	 *
	 *     # Search only the second window of 1000 candidate posts.
	 *     $ wp block search --block=core/paragraph --posts_per_page=1000 --paged=2 --format=ids
	 *
	 *     # Restrict search to specific posts and return the count.
	 *     $ wp block search --style=rounded --post__in=21,42,84 --format=count
	 *
	 *     # Return the first 20 posts using the paragraph block.
	 *     $ wp block search --block=core/paragraph --limit=20 --format=ids
	 *
	 *     # Return only matching post IDs.
	 *     $ wp block search --block=core/paragraph --format=ids
	 *
	 *     # Return count of matching posts.
	 *     $ wp block search --block=core/heading --format=count
	 *
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
		$synced_pattern  = null;

		$synced_pattern_raw = Utils\get_flag_value( $assoc_args, 'synced-pattern', null );

		if ( null !== $synced_pattern_raw && '' !== $synced_pattern_raw ) {
			if ( ! is_scalar( $synced_pattern_raw ) || 1 !== preg_match( '/^\d+$/', (string) $synced_pattern_raw ) || (int) $synced_pattern_raw <= 0 ) {
				WP_CLI::error( 'The --synced-pattern parameter must be a positive integer post ID.' );
			}

			$synced_pattern = (int) $synced_pattern_raw;
		}

		$limit     = null;
		$limit_raw = Utils\get_flag_value( $assoc_args, 'limit', null );

		if ( null !== $limit_raw ) {
			if ( ! is_scalar( $limit_raw ) || 1 !== preg_match( '/^\d+$/', (string) $limit_raw ) || (int) $limit_raw <= 0 ) {
				WP_CLI::error( 'The --limit parameter must be a positive integer.' );
			}

			if ( isset( $assoc_args['paged'] ) ) {
				WP_CLI::error( 'The --limit parameter cannot be combined with --paged.' );
			}

			$limit = (int) $limit_raw;
		}

		if ( ( null === $block_name || '' === $block_name ) && ( null === $block_namespace || '' === $block_namespace ) && '' === $style_name && ( null === $pattern_name || '' === $pattern_name ) && ( null === $pattern_ns || '' === $pattern_ns ) && null === $synced_pattern ) {
			WP_CLI::error( 'At least one block filter is required: --block, --block-namespace, --style, --pattern, --pattern-namespace, or --synced-pattern.' );
		}

		if ( null !== $block_name && '' !== $block_name && null !== $block_namespace && '' !== $block_namespace ) {
			WP_CLI::error( 'The --block and --block-namespace parameters are mutually exclusive.' );
		}

		if ( null !== $pattern_name && '' !== $pattern_name && null !== $pattern_ns && '' !== $pattern_ns ) {
			WP_CLI::error( 'The --pattern and --pattern-namespace parameters are mutually exclusive.' );
		}

		if ( null !== $synced_pattern && ( ( null !== $block_name && '' !== $block_name ) || ( null !== $block_namespace && '' !== $block_namespace ) ) ) {
			WP_CLI::error( 'The --synced-pattern parameter cannot be combined with --block or --block-namespace.' );
		}

		if ( null !== $block_name && '' !== $block_name && false === strpos( $block_name, '/' ) ) {
			$block_name = 'core/' . $block_name;
		}

		$defaults = [
			'post_type'              => 'any',
			'post_status'            => 'any',
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
			$query_assoc_args['limit'],
			$query_assoc_args['field'],
			$query_assoc_args['fields'],
			$query_assoc_args['format']
		);

		$query_args = array_merge( $defaults, $query_assoc_args );
		$query_args = self::process_csv_arguments_to_arrays( $query_args );

		if ( isset( $query_args['post_type'] ) && 'any' !== $query_args['post_type'] ) {
			$query_args['post_type'] = explode( ',', $query_args['post_type'] );
		}

		$rough_prefilter = $this->build_rough_post_content_prefilter(
			$block_name,
			$block_namespace,
			$style_name,
			$pattern_name,
			$pattern_ns,
			$synced_pattern
		);

		$include_url         = $this->is_field_requested( $assoc_args, 'url' );
		$include_occurrences = $this->is_field_requested( $assoc_args, 'occurrences' );
		$use_fast_path       = $this->can_use_has_block_fast_path( $block_name, $block_namespace, $style_name, $pattern_name, $pattern_ns, $synced_pattern );
		$results             = [];

		foreach ( $this->query_posts_in_batches( $query_args, $rough_prefilter ) as $post ) {
			$occurrences = null;

			if ( $use_fast_path && ! has_block( $block_name, $post ) ) {
				continue;
			}

			if ( ! $use_fast_path || $include_occurrences ) {
				$matches = $this->find_matching_blocks( $post->post_content, $block_name, $block_namespace, $style_name, $pattern_name, $pattern_ns, $synced_pattern, ! $include_occurrences );

				if ( empty( $matches ) ) {
					continue;
				}

				$occurrences = count( $matches );
			}

			$result = [
				'ID'          => $post->ID,
				'post_title'  => $post->post_title,
				'post_name'   => $post->post_name,
				'post_date'   => $post->post_date,
				'post_type'   => $post->post_type,
				'post_status' => $post->post_status,
			];

			if ( $include_url ) {
				$result['url'] = get_permalink( $post->ID );
			}

			if ( null !== $occurrences ) {
				$result['occurrences'] = $occurrences;
			}

			$results[] = $result;

			if ( null !== $limit && count( $results ) >= $limit ) {
				break;
			}
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
	 * Checks whether an output field was explicitly requested.
	 *
	 * @param array  $assoc_args Associative arguments.
	 * @param string $field Field name.
	 * @return bool
	 */
	private function is_field_requested( array $assoc_args, $field ) {
		$requested = [];

		foreach ( [ 'field', 'fields' ] as $key ) {
			if ( isset( $assoc_args[ $key ] ) && is_string( $assoc_args[ $key ] ) ) {
				$requested = array_merge( $requested, array_map( 'trim', explode( ',', $assoc_args[ $key ] ) ) );
			}
		}

		return in_array( $field, $requested, true );
	}

	/**
	 * Yields candidate posts, loading them in batches of IDs.
	 *
	 * The prefiltered query only selects IDs. Posts are then loaded in fixed-size
	 * batches so memory stays flat regardless of how many posts are candidates.
	 * Explicit pagination args still select exactly that window of IDs.
	 *
	 * @param array    $query_args WP_Query arguments.
	 * @param array<int, string|array<mixed>> $markers Rough prefilter markers.
	 * @return \Generator<\WP_Post>
	 */
	private function query_posts_in_batches( array $query_args, array $markers ) {
		$id_query_args           = $query_args;
		$id_query_args['fields'] = 'ids';

		$has_page_size = false;

		foreach ( [ 'posts_per_page', 'showposts', 'nopaging' ] as $key ) {
			if ( isset( $query_args[ $key ] ) ) {
				$has_page_size = true;
				break;
			}
		}

		if ( ! $has_page_size && ! isset( $query_args['paged'] ) ) {
			// -1 would make WP_Query ignore `offset`, so use an effectively unbounded size when one is given.
			$id_query_args['posts_per_page'] = isset( $query_args['offset'] ) ? PHP_INT_MAX : -1;
		}

		if ( ! isset( $query_args['orderby'] ) ) {
			$id_query_args['orderby'] = 'ID';
			$id_query_args['order']   = 'ASC';
		}

		$found = $this->run_query_with_rough_prefilter( $id_query_args, $markers )->posts;
		$ids   = array_map(
			static function ( $id ) {
				return (int) ( $id instanceof \WP_Post ? $id->ID : $id );
			},
			is_array( $found ) ? $found : []
		);

		foreach ( array_chunk( $ids, self::BATCH_SIZE ) as $batch_ids ) {
			$batch = new \WP_Query(
				[
					'post__in'               => $batch_ids,
					'post_type'              => $query_args['post_type'],
					'post_status'            => $query_args['post_status'],
					'orderby'                => 'ID',
					'posts_per_page'         => count( $batch_ids ),
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'cache_results'          => false,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$batch_posts = $batch->posts;
			$by_id       = [];

			foreach ( is_array( $batch_posts ) ? $batch_posts : [] as $post ) {
				if ( $post instanceof \WP_Post ) {
					$by_id[ $post->ID ] = $post;
				}
			}

			// Restore the order of the ID query in PHP, as ORDER BY FIELD() with 200 IDs fails on some versions of SQLite.
			foreach ( $batch_ids as $batch_id ) {
				if ( isset( $by_id[ $batch_id ] ) ) {
					yield $by_id[ $batch_id ];
				}
			}
		}
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
	 * Builds a rough post_content prefilter marker.
	 *
	 * The prefilter is advisory only and must never exclude valid matches.
	 *
	 * @param string|null $block_name Requested exact block name.
	 * @param string|null $block_namespace Requested block namespace.
	 * @param string      $style_name Requested style name.
	 * @param string|null $pattern_name Requested exact pattern name.
	 * @param string|null $pattern_namespace Requested pattern namespace.
	 * @param int|null    $synced_pattern Requested synced pattern post ID.
	 * @return array<int, string|array<mixed>> Markers that must all match; a list entry matches if any alternative does, and a `regexp` entry carries a LIKE `fallback`.
	 */
	private function build_rough_post_content_prefilter( $block_name, $block_namespace, $style_name, $pattern_name, $pattern_namespace, $synced_pattern ) {
		$markers = [];

		if ( null !== $block_name && '' !== $block_name ) {
			$markers[] = $this->alternatives(
				[
					'<!-- wp:' . $this->strip_core_block_namespace( $block_name ),
					'<!-- wp:' . $block_name,
				]
			);
		}

		if ( null !== $block_namespace && '' !== $block_namespace ) {
			$markers[] = $this->build_block_namespace_prefilter_marker( $block_namespace );
		}

		if ( '' !== $style_name ) {
			$markers[] = $this->alternatives(
				[
					'is-style-' . $style_name,
					'is-style-' . $this->encode_attribute_value( $style_name ),
				]
			);
		}

		if ( null !== $pattern_name && '' !== $pattern_name ) {
			$markers[] = '"patternName"';
			$markers[] = $this->alternatives( [ $pattern_name, $this->encode_attribute_value( $pattern_name ) ] );
		}

		if ( null !== $pattern_namespace && '' !== $pattern_namespace ) {
			$markers[] = '"patternName"';
			$markers[] = $this->alternatives( [ $pattern_namespace . '/', $this->encode_attribute_value( $pattern_namespace . '/' ) ] );
		}

		if ( null !== $synced_pattern ) {
			$markers[] = '"ref"';
			$markers[] = [
				'regexp'   => '"ref"[[:space:]]*:[[:space:]]*' . $synced_pattern . '([^0-9]|$)',
				'fallback' => [ '"ref":' . $synced_pattern, '"ref": ' . $synced_pattern ],
			];
		}

		return array_values( array_unique( $markers, SORT_REGULAR ) );
	}

	/**
	 * Checks whether the database accepts REGEXP, caching the answer.
	 *
	 * @return bool
	 */
	private function supports_regexp() {
		global $wpdb;

		if ( null === $this->regexp_supported ) {
			$suppress               = $wpdb->suppress_errors( true );
			$result                 = $wpdb->get_var( "SELECT 'a' REGEXP 'a'" );
			$this->regexp_supported = '1' === (string) $result;
			$wpdb->suppress_errors( $suppress );
		}

		return $this->regexp_supported;
	}

	/**
	 * Collapses identical alternatives into a single marker.
	 *
	 * @param string[] $alternatives Candidate markers, any of which may appear in the content.
	 * @return string|string[]
	 */
	private function alternatives( array $alternatives ) {
		$alternatives = array_values( array_unique( $alternatives ) );

		return 1 === count( $alternatives ) ? $alternatives[0] : $alternatives;
	}

	/**
	 * Encodes a value the way block attributes are escaped when serialized.
	 *
	 * @param string $value Raw attribute value.
	 * @return string
	 */
	private function encode_attribute_value( $value ) {
		$encoded = wp_json_encode( (string) $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( ! is_string( $encoded ) ) {
			return (string) $value;
		}

		$encoded = substr( $encoded, 1, -1 );
		$encoded = str_replace( [ '--', '<', '>', '&' ], [ '\u002d\u002d', '\u003c', '\u003e', '\u0026' ], $encoded );

		return str_replace( '\\"', '\u0022', $encoded );
	}

	/**
	 * Runs a query with an optional rough post_content prefilter.
	 *
	 * @param array    $query_args WP_Query arguments.
	 * @param array<int, string|array<mixed>> $markers Rough prefilter markers.
	 * @return \WP_Query
	 */
	private function run_query_with_rough_prefilter( array $query_args, array $markers ) {
		if ( [] === $markers ) {
			return new \WP_Query( $query_args );
		}

		$query_args['wp_cli_block_search_rough_markers'] = $markers;

		$posts_where = [ $this, 'filter_posts_where_for_rough_post_content_prefilter' ];
		add_filter( 'posts_where', $posts_where, 10, 2 );

		try {
			return new \WP_Query( $query_args );
		} finally {
			remove_filter( 'posts_where', $posts_where, 10 );
		}
	}

	/**
	 * Applies the rough post_content prefilter to this command's query only.
	 *
	 * @param string    $where The WHERE clause.
	 * @param \WP_Query $query Query instance.
	 * @return string
	 */
	public function filter_posts_where_for_rough_post_content_prefilter( $where, $query ) {
		global $wpdb;

		$markers = $query->get( 'wp_cli_block_search_rough_markers' );

		if ( ! is_array( $markers ) || [] === $markers ) {
			return $where;
		}

		$clauses = [];

		foreach ( $markers as $marker ) {
			if ( is_array( $marker ) && isset( $marker['regexp'] ) ) {
				if ( $this->supports_regexp() ) {
					$clauses[] = $wpdb->prepare( "{$wpdb->posts}.post_content REGEXP %s", $marker['regexp'] );
					continue;
				}

				$marker = $marker['fallback'];
			}

			$likes = [];

			foreach ( (array) $marker as $alternative ) {
				if ( ! is_string( $alternative ) || '' === $alternative ) {
					continue;
				}

				$likes[] = $wpdb->prepare(
					"{$wpdb->posts}.post_content LIKE %s",
					'%' . $wpdb->esc_like( $alternative ) . '%'
				);
			}

			if ( [] !== $likes ) {
				$clauses[] = implode( ' OR ', $likes );
			}
		}

		if ( [] === $clauses ) {
			return $where;
		}

		return $where . ' AND ' . implode(
			' AND ',
			array_map(
				static function ( $clause ) {
					return '(' . $clause . ')';
				},
				$clauses
			)
		);
	}

	/**
	 * Normalizes core block names to their serialized comment marker form.
	 *
	 * @param string $block_name Requested exact block name.
	 * @return string
	 */
	private function strip_core_block_namespace( $block_name ) {
		if ( 0 === strpos( $block_name, 'core/' ) ) {
			return substr( $block_name, 5 );
		}

		return $block_name;
	}

	/**
	 * Builds a rough serialized comment marker for a block namespace.
	 *
	 * Core blocks omit the namespace in serialized comments, so the safest
	 * coarse marker for core is the generic block comment prefix.
	 *
	 * @param string $block_namespace Requested block namespace.
	 * @return string
	 */
	private function build_block_namespace_prefilter_marker( $block_namespace ) {
		if ( 'core' === $block_namespace ) {
			return '<!-- wp:';
		}

		return '<!-- wp:' . $block_namespace . '/';
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
	 * @param bool        $first_only Stop at the first match when only existence is needed.
	 * @return array
	 */
	private function find_matching_blocks( $content, $block_name, $block_namespace, $style_name, $pattern_name, $pattern_namespace, $synced_pattern, $first_only = false ) {
		$blocks  = parse_blocks( $content );
		$matches = [];

		foreach ( $this->iterate_blocks( $blocks ) as $block ) {
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

			if ( $first_only ) {
				break;
			}
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

		$block_pattern_name = $this->get_block_pattern_name( $block );

		if ( '' === $block_pattern_name ) {
			return false;
		}

		if ( null !== $pattern_name && '' !== $pattern_name ) {
			return $block_pattern_name === $pattern_name;
		}

		return 0 === strpos( $block_pattern_name, $pattern_namespace . '/' );
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
	 * Gets the pattern name stored in the block's own metadata.
	 *
	 * @param array $block Parsed block.
	 * @return string
	 */
	private function get_block_pattern_name( array $block ) {
		if ( isset( $block['attrs']['metadata']['patternName'] ) && is_string( $block['attrs']['metadata']['patternName'] ) ) {
			return $block['attrs']['metadata']['patternName'];
		}

		return '';
	}

	/**
	 * Lazily walks nested block arrays depth-first.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return \Generator<array>
	 */
	private function iterate_blocks( array $blocks ) {
		foreach ( $blocks as $block ) {
			yield $block;

			if ( ! empty( $block['innerBlocks'] ) ) {
				yield from $this->iterate_blocks( $block['innerBlocks'] );
			}
		}
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

		$needle = 'is-style-' . $style_name;
		if ( 1 === preg_match( '/(^|\\s)' . preg_quote( $needle, '/' ) . '(\\s|$)/', $class_name ) ) {
			return true;
		}

		return ! empty( $block['innerHTML'] ) && 1 === preg_match(
			"/(^|[\\s\"'])" . preg_quote( $needle, '/' ) . "([\\s\"']|$)/",
			$block['innerHTML']
		);
	}
}
