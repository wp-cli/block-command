Feature: Block pattern category commands

  @require-wp-5.5
  Scenario: List registered pattern categories
    Given a WP install

    When I run `wp block pattern-category list --format=count`
    Then STDOUT should be a number

    When I run `wp block pattern-category list --format=json`
    Then STDOUT should not be empty

    When I run `wp block pattern-category list --field=name`
    Then STDOUT should not be empty

  @require-wp-5.5
  Scenario: Get a pattern category
    Given a WP install

    # 'text' is a core category that should always exist
    When I run `wp block pattern-category get text --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"text"}
      """

    When I run `wp block pattern-category get text --field=name`
    Then STDOUT should be:
      """
      text
      """

  @require-wp-5.5
  Scenario: Get a non-existent category
    Given a WP install

    When I try `wp block pattern-category get nonexistent`
    Then STDERR should contain:
      """
      not registered
      """
    And the return code should be 1
