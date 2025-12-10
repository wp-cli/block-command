Feature: Block pattern commands

  @require-wp-5.5
  Scenario: List registered block patterns
    Given a WP install

    When I run `wp block pattern list --format=count`
    Then STDOUT should be a number

    When I run `wp block pattern list --format=json`
    Then STDOUT should not be empty

    When I run `wp block pattern list --format=ids`
    Then STDOUT should not be empty

  @require-wp-5.5
  Scenario: Filter patterns by category
    Given a WP install

    # List all patterns in a category
    When I run `wp block pattern list --category=text --format=count`
    Then STDOUT should be a number

  @require-wp-5.5
  Scenario: Search patterns by title
    Given a WP install

    # Search patterns - this tests the --search functionality
    When I run `wp block pattern list --search=header --format=count`
    Then STDOUT should be a number

  @require-wp-5.5
  Scenario: Get a block pattern
    Given a WP install

    # Get the first pattern name
    When I run `wp block pattern list --field=name`
    Then STDOUT should not be empty
    And save STDOUT '%s' as {PATTERN_NAME}

    # Get the pattern details
    When I run `wp block pattern get {PATTERN_NAME} --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"{PATTERN_NAME}"}
      """

    # Get a specific field
    When I run `wp block pattern get {PATTERN_NAME} --field=name`
    Then STDOUT should be:
      """
      {PATTERN_NAME}
      """

  @require-wp-5.5
  Scenario: Get a non-existent pattern
    Given a WP install

    When I try `wp block pattern get nonexistent/pattern`
    Then STDERR should contain:
      """
      not registered
      """
    And the return code should be 1
