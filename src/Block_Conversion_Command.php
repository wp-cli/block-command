<?php

namespace WP_CLI\Block;

use WP_CLI;
use WP_CLI\Formatter;
use WP_CLI_Command;

/**
 * Converts HTML to block markup with the server-side block conversion.
 *
 * Requires the server-side block conversion introduced in
 * https://github.com/WordPress/gutenberg/pull/82013, so a Gutenberg build
 * from that pull request has to be installed and active.
 *
 * ## EXAMPLES
 *
 *     # Convert HTML to block markup
 *     $ wp block convert '<h2>Title</h2><p>Text</p>'
 *
 *     # List the blocks a conversion can produce
 *     $ wp block conversion-support
 *
 * @package wp-cli
 */
class Block_Conversion_Command extends WP_CLI_Command {

	/**
	 * Pull request that provides the server-side block conversion.
	 *
	 * @var string
	 */
	const GUTENBERG_PR_URL = 'https://github.com/WordPress/gutenberg/pull/82013';

	/**
	 * Converts HTML to block markup.
	 *
	 * Markup that no block claims is kept verbatim inside a Custom HTML block.
	 * Input that already contains block delimiters is parsed as blocks rather
	 * than converted again.
	 *
	 * The result is printed as-is and is not sanitized. Run it through the
	 * usual filters, for example by saving it with `wp post update`, before
	 * storing it as post content.
	 *
	 * Requires an active Gutenberg build from
	 * https://github.com/WordPress/gutenberg/pull/82013.
	 *
	 * ## OPTIONS
	 *
	 * [<html>]
	 * : The HTML to convert. Reads from --file or STDIN when omitted.
	 *
	 * [--file=<file>]
	 * : Read the HTML from a file instead of the argument.
	 *
	 * ## EXAMPLES
	 *
	 *     # Convert HTML passed as an argument
	 *     $ wp block convert '<h2>Title</h2><p>Text</p>'
	 *     <!-- wp:heading -->
	 *     <h2 class="wp-block-heading">Title</h2>
	 *     <!-- /wp:heading -->
	 *
	 *     <!-- wp:paragraph -->
	 *     <p>Text</p>
	 *     <!-- /wp:paragraph -->
	 *
	 *     # Convert the contents of a file
	 *     $ wp block convert --file=page.html
	 *
	 *     # Convert HTML from STDIN
	 *     $ cat page.html | wp block convert
	 *
	 *     # Convert a post's content in place
	 *     $ wp post get 123 --field=post_content | wp block convert | wp post update 123 -
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function convert( $args, $assoc_args ) {
		$this->check_conversion_available( 'gutenberg_html_to_block_markup' );

		$html = $this->read_html( $args, $assoc_args );

		WP_CLI::line( gutenberg_html_to_block_markup( $html ) );
	}

	/**
	 * Lists the blocks a server-side conversion can produce.
	 *
	 * Reports one row per block. The `support` column is one of:
	 *
	 * * converts: produced from any markup the block matches.
	 * * conditional: produced only from markup the block can save back.
	 * * declines: deliberately not produced by a server-side conversion.
	 *
	 * Requires an active Gutenberg build from
	 * https://github.com/WordPress/gutenberg/pull/82013.
	 *
	 * ## OPTIONS
	 *
	 * [--support=<support>]
	 * : Only list blocks with this level of support.
	 * ---
	 * options:
	 *   - converts
	 *   - conditional
	 *   - declines
	 * ---
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each block.
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
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each block:
	 *
	 * * name
	 * * support
	 *
	 * ## EXAMPLES
	 *
	 *     # List what a conversion can produce
	 *     $ wp block conversion-support
	 *     +----------------+-------------+
	 *     | name           | support     |
	 *     +----------------+-------------+
	 *     | core/heading   | converts    |
	 *     | core/paragraph | converts    |
	 *     | core/table     | declines    |
	 *     +----------------+-------------+
	 *
	 *     # List only the blocks that decline conversion
	 *     $ wp block conversion-support --support=declines --field=name
	 *
	 *     # Get the support map as JSON
	 *     $ wp block conversion-support --format=json
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function conversion_support( $args, $assoc_args ) {
		$this->check_conversion_available( 'gutenberg_get_block_conversion_support' );

		$support = gutenberg_get_block_conversion_support();
		$levels  = [ 'converts', 'conditional', 'declines' ];

		if ( ! empty( $assoc_args['support'] ) ) {
			$levels = [ $assoc_args['support'] ];
			unset( $assoc_args['support'] );
		}

		$items = [];
		foreach ( $levels as $level ) {
			if ( empty( $support[ $level ] ) ) {
				continue;
			}
			foreach ( $support[ $level ] as $name ) {
				$items[] = [
					'name'    => $name,
					'support' => $level,
				];
			}
		}

		usort(
			$items,
			static function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		$formatter = new Formatter( $assoc_args, [ 'name', 'support' ], 'block-conversion-support' );
		$formatter->display_items( $items );
	}

	/**
	 * Fails unless the given conversion function is available.
	 *
	 * The conversion functions only exist while a Gutenberg build from the
	 * pull request that introduces them is active.
	 *
	 * @param string $function_name Function the command is about to call.
	 */
	private function check_conversion_available( $function_name ) {
		if ( ! function_exists( $function_name ) ) {
			WP_CLI::error(
				'Server-side block conversion is not available. Activate the Gutenberg plugin from ' . self::GUTENBERG_PR_URL . '.'
			);
		}
	}

	/**
	 * Reads the HTML to convert from the argument, --file, or STDIN.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return string HTML to convert.
	 */
	private function read_html( $args, $assoc_args ) {
		$has_arg  = isset( $args[0] );
		$has_file = isset( $assoc_args['file'] );

		if ( $has_arg && $has_file ) {
			WP_CLI::error( 'Pass the HTML either as an argument or with --file, not both.' );
		}

		if ( $has_arg ) {
			$html = $args[0];
		} elseif ( $has_file ) {
			$html = $this->read_file( $assoc_args['file'] );
		} else {
			$html = $this->read_stdin();
		}

		if ( '' === trim( $html ) ) {
			WP_CLI::error( 'No HTML given. Pass it as an argument, with --file, or on STDIN.' );
		}

		return $html;
	}

	/**
	 * Reads a file.
	 *
	 * @param string $file Path to the file.
	 * @return string File contents.
	 */
	private function read_file( $file ) {
		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			WP_CLI::error( "File '{$file}' does not exist or is not readable." );
		}

		$contents = file_get_contents( $file );

		if ( false === $contents ) {
			WP_CLI::error( "Failed to read file '{$file}'." );
		}

		return $contents;
	}

	/**
	 * Reads STDIN.
	 *
	 * Treats an interactive terminal as no input rather than waiting for it.
	 *
	 * @return string STDIN contents.
	 */
	private function read_stdin() {
		if ( function_exists( 'stream_isatty' ) && stream_isatty( STDIN ) ) {
			return '';
		}

		$contents = file_get_contents( 'php://stdin' );

		if ( false === $contents ) {
			WP_CLI::error( 'Failed to read from STDIN.' );
		}

		return $contents;
	}
}
