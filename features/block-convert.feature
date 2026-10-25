Feature: Server-side block conversion commands

  # The conversion functions ship with the Gutenberg build from
  # https://github.com/WordPress/gutenberg/pull/82013 and are not part of any
  # released WordPress or Gutenberg version yet, so on a plain install every
  # command fails with the same error.

  @require-wp-5.0
  Scenario: Convert fails without the server-side block conversion
    Given a WP install

    When I try `wp block convert '<p>Text</p>'`
    Then STDERR should be:
      """
      Error: Server-side block conversion is not available. Activate the Gutenberg plugin from https://github.com/WordPress/gutenberg/pull/82013.
      """
    And STDOUT should be empty
    And the return code should be 1

  @require-wp-5.0
  Scenario: Conversion support fails without the server-side block conversion
    Given a WP install

    When I try `wp block conversion-support`
    Then STDERR should be:
      """
      Error: Server-side block conversion is not available. Activate the Gutenberg plugin from https://github.com/WordPress/gutenberg/pull/82013.
      """
    And STDOUT should be empty
    And the return code should be 1

  # The scenarios below need a Gutenberg build from that pull request, which
  # CI cannot install, so they are tagged @broken and `composer behat` skips
  # them. To run them locally:
  #
  # 1. Build the plugin zip from the `try/13163-php-block-conversion` branch
  #    (`npm run build:plugin-zip` in a Gutenberg checkout).
  # 2. Copy it to `gutenberg.zip` in the root of this package. Git ignores it.
  # 3. Prepare the test database once with `composer prepare-tests`.
  # 4. Run the scenarios by tag, bypassing the @broken exclusion:
  #
  #        vendor/bin/behat --tags=@broken features/block-convert.feature
  #
  # Each scenario installs the zip with `wp plugin install gutenberg.zip --activate`.

  @broken
  Scenario: Convert HTML passed as an argument
    Given a WP install
    And I run `wp plugin install {PROJECT_DIR}/gutenberg.zip --activate`

    When I run `wp block convert '<h2>Title</h2><p>Text</p>'`
    Then STDOUT should contain:
      """
      <!-- wp:heading -->
      """
    And STDOUT should contain:
      """
      <!-- wp:paragraph -->
      """
    And STDOUT should contain:
      """
      <p>Text</p>
      """
    And STDERR should be empty

  @broken
  Scenario: Convert HTML from STDIN and from a file
    Given a WP install
    And I run `wp plugin install {PROJECT_DIR}/gutenberg.zip --activate`
    And a page.html file:
      """
      <h2>Title</h2>
      <p>Text</p>
      """

    When I run `cat page.html | wp block convert`
    Then STDOUT should contain:
      """
      <!-- wp:heading -->
      """
    And STDOUT should contain:
      """
      <!-- wp:paragraph -->
      """

    When I run `wp block convert --file=page.html`
    Then STDOUT should contain:
      """
      <!-- wp:heading -->
      """
    And STDOUT should contain:
      """
      <!-- wp:paragraph -->
      """

    When I try `wp block convert --file=missing.html`
    Then STDERR should contain:
      """
      Error: File 'missing.html' does not exist or is not readable.
      """
    And the return code should be 1

    When I try `wp block convert '<p>Text</p>' --file=page.html`
    Then STDERR should contain:
      """
      Error: Pass the HTML either as an argument or with --file, not both.
      """
    And the return code should be 1

    When I try `wp block convert < /dev/null`
    Then STDERR should contain:
      """
      Error: No HTML given. Pass it as an argument, with --file, or on STDIN.
      """
    And the return code should be 1

  @broken
  Scenario: Existing block markup is left alone and unknown markup becomes a Custom HTML block
    Given a WP install
    And I run `wp plugin install {PROJECT_DIR}/gutenberg.zip --activate`

    When I run `wp block convert '<!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph -->'`
    Then STDOUT should be:
      """
      <!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph -->
      """

    When I run `wp block convert '<marquee>Text</marquee>'`
    Then STDOUT should contain:
      """
      <!-- wp:html -->
      """

  @broken
  Scenario: List the blocks a conversion can produce
    Given a WP install
    And I run `wp plugin install {PROJECT_DIR}/gutenberg.zip --activate`

    When I run `wp block conversion-support`
    Then STDOUT should be a table containing rows:
      | name           | support  |
      | core/paragraph | converts |
      | core/table     | declines |

    When I run `wp block conversion-support --support=declines --field=name`
    Then STDOUT should contain:
      """
      core/table
      """
    And STDOUT should not contain:
      """
      core/paragraph
      """

    When I run `wp block conversion-support --format=json`
    Then STDOUT should contain:
      """
      {"name":"core\/paragraph","support":"converts"}
      """

    When I run `wp block conversion-support --format=count`
    Then STDOUT should be a number
