Feature: Block style commands

  @require-wp-5.3
  Scenario: List registered block styles
    Given a WP install

    When I run `wp block style list --format=count`
    Then STDOUT should be a number

    When I run `wp block style list --format=json`
    Then STDOUT should not be empty

  @require-wp-5.3
  Scenario: List styles for a specific block
    Given a WP install

    # core/button block has styles registered by core
    When I run `wp block style list --block=core/button --format=count`
    Then STDOUT should be a number

    When I run `wp block style list --block=core/button --format=json`
    Then STDOUT should not be empty

  @require-wp-5.3
  Scenario: Get a specific block style from custom theme
    Given a WP install
    # Create a minimal theme that registers a block style
    And a wp-content/themes/test-theme/style.css file:
      """
      /*
      Theme Name: Test Theme
      Version: 1.0.0
      */
      """
    And a wp-content/themes/test-theme/index.php file:
      """
      <?php
      // Silence is golden.
      """
    And a wp-content/themes/test-theme/functions.php file:
      """
      <?php
      add_action( 'init', function() {
          register_block_style( 'core/paragraph', array(
              'name'  => 'fancy-quote',
              'label' => 'Fancy Quote',
          ) );
      } );
      """

    When I run `wp theme activate test-theme`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp block style list --block=core/paragraph --field=name`
    Then STDOUT should contain:
      """
      fancy-quote
      """

    When I run `wp block style get core/paragraph fancy-quote --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"fancy-quote","label":"Fancy Quote"}
      """

    When I run `wp block style get core/paragraph fancy-quote --field=label`
    Then STDOUT should be:
      """
      Fancy Quote
      """

  @require-wp-5.3
  Scenario: Get a non-existent style
    Given a WP install

    When I try `wp block style get core/button nonexistent-style`
    Then STDERR should contain:
      """
      not registered
      """
    And the return code should be 1

  @require-wp-5.3
  Scenario: List styles for non-existent block returns zero
    Given a WP install

    # Test behavior when block doesn't have styles registered
    When I run `wp block style list --block=nonexistent/block --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.3
  Scenario: List styles for block with no registered styles
    Given a WP install

    # core/html typically has no styles registered
    When I run `wp block style list --block=core/html --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.3
  Scenario: List block styles in various formats
    Given a WP install
    # Create theme with style to ensure we have data
    And a wp-content/themes/format-test-theme/style.css file:
      """
      /*
      Theme Name: Format Test Theme
      */
      """
    And a wp-content/themes/format-test-theme/index.php file:
      """
      <?php
      """
    And a wp-content/themes/format-test-theme/functions.php file:
      """
      <?php
      add_action( 'init', function() {
          register_block_style( 'core/paragraph', array(
              'name'  => 'format-test-style',
              'label' => 'Format Test Style',
          ) );
      } );
      """

    When I run `wp theme activate format-test-theme`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `wp block style list --block=core/paragraph --field=name`
    Then STDOUT should contain:
      """
      format-test-style
      """

    When I run `wp block style list --block=core/paragraph --format=yaml`
    Then STDOUT should contain:
      """
      name: format-test-style
      """

    When I run `wp block style list --block=core/paragraph --format=csv`
    Then STDOUT should contain:
      """
      name,
      """
