Feature: Block type commands

  @require-wp-5.0
  Scenario: List registered block types
    Given a WP install

    When I run `wp block type list --format=count`
    Then STDOUT should be a number
    And STDOUT should not be empty

    When I run `wp block type list --format=json`
    Then STDOUT should not be empty

    When I run `wp block type list --format=ids`
    Then STDOUT should contain:
      """
      core/paragraph
      """

  @require-wp-5.0
  Scenario: Filter block types by namespace
    Given a WP install

    When I run `wp block type list --namespace=core --field=name`
    Then STDOUT should contain:
      """
      core/paragraph
      """
    And STDOUT should contain:
      """
      core/heading
      """
    And STDOUT should not contain:
      """
      woocommerce/
      """

  @require-wp-5.0
  Scenario: Filter by dynamic blocks
    Given a WP install

    # Dynamic blocks have render_callback, like core/archives
    When I run `wp block type list --dynamic --field=name`
    Then STDOUT should contain:
      """
      core/archives
      """

    # Static blocks don't have render_callback, like core/paragraph
    When I run `wp block type list --static --field=name`
    Then STDOUT should contain:
      """
      core/paragraph
      """

    # Verify dynamic and static are mutually exclusive
    When I run `wp block type list --dynamic --format=count`
    Then save STDOUT as {DYNAMIC_COUNT}

    When I run `wp block type list --static --format=count`
    Then save STDOUT as {STATIC_COUNT}

    When I run `wp block type list --format=count`
    Then STDOUT should be a number

  @require-wp-5.0
  Scenario: Get a specific block type
    Given a WP install

    When I run `wp block type get core/paragraph --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"core/paragraph"}
      """

    When I run `wp block type get core/paragraph --field=name`
    Then STDOUT should be:
      """
      core/paragraph
      """

    # Verify category field is accessible
    When I run `wp block type get core/paragraph --field=category`
    Then STDOUT should be:
      """
      text
      """

    # Verify is_dynamic is accessible (static blocks return empty/falsy)
    When I run `wp block type get core/paragraph --format=json`
    Then STDOUT should be JSON containing:
      """
      {"is_dynamic":false}
      """

    # Verify dynamic block has is_dynamic=true
    When I run `wp block type get core/archives --format=json`
    Then STDOUT should be JSON containing:
      """
      {"is_dynamic":true}
      """

  @require-wp-5.0
  Scenario: Get a non-existent block type
    Given a WP install

    When I try `wp block type get nonexistent/block`
    Then STDERR should contain:
      """
      not registered
      """
    And the return code should be 1
