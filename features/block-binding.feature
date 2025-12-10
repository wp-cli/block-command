Feature: Block binding commands

  @less-than-wp-6.5
  Scenario: Block binding commands not available on WP < 6.5
    Given a WP install

    When I try `wp block binding list`
    Then STDERR should contain:
      """
      is not a registered wp command
      """
    And the return code should be 1

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

  @require-wp-6.5
  Scenario: List binding sources in various formats
    Given a WP install

    When I run `wp block binding list --format=table`
    Then STDOUT should be a table containing rows:
      | name           |
      | core/post-meta |

    When I run `wp block binding list --format=yaml`
    Then STDOUT should contain:
      """
      name: core/post-meta
      """

    When I run `wp block binding list --format=csv`
    Then STDOUT should contain:
      """
      name,
      """

  @require-wp-6.5
  Scenario: Get binding source field values
    Given a WP install

    When I run `wp block binding get core/post-meta --field=name`
    Then STDOUT should be:
      """
      core/post-meta
      """

    When I run `wp block binding get core/post-meta --field=label`
    Then STDOUT should not be empty
