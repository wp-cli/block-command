<?php
/**
 * PHPStan stubs for the server-side block conversion from
 * https://github.com/WordPress/gutenberg/pull/82013.
 *
 * The functions only exist while a Gutenberg build from that pull request is
 * active, so `wp block convert` and `wp block conversion-support` check for
 * them at runtime. This file gives PHPStan their signatures.
 */

/**
 * Converts HTML into blocks.
 *
 * @param string $html HTML to convert.
 * @return array<int, array<string, mixed>> Parsed block arrays, in the shape returned by `parse_blocks()`.
 */
function gutenberg_html_to_blocks( $html ) {}

/**
 * Converts HTML into serialized block markup.
 *
 * @param string $html HTML to convert.
 * @return string Block markup.
 */
function gutenberg_html_to_block_markup( $html ) {}

/**
 * Reports which blocks a server-side conversion can produce.
 *
 * @return array{converts: string[], conditional: string[], declines: string[]}
 */
function gutenberg_get_block_conversion_support() {}
