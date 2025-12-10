Feature: Block binding commands

  @require-wp-6.5
  Scenario: List block binding sources
    Given a WP install

    When I run `wp block binding list --format=count`
    Then STDOUT should be a number

    When I run `wp block binding list --format=json`
    Then STDOUT should not be empty

    When I run `wp block binding list --field=name`
    Then STDOUT should contain:
      """
      core/post-meta
      """

  @require-wp-6.5
  Scenario: Get a block binding source
    Given a WP install

    When I run `wp block binding get core/post-meta --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"core/post-meta"}
      """

  @require-wp-6.5
  Scenario: Get a non-existent binding source
    Given a WP install

    When I try `wp block binding get nonexistent/source`
    Then STDERR should contain:
      """
      not registered
      """
    And the return code should be 1
