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
      core/archives
      """

  @require-wp-5.0
  Scenario: Filter block types by namespace
    Given a WP install

    When I run `wp block type list --namespace=core --field=name`
    Then STDOUT should contain:
      """
      core/archives
      """
    And STDOUT should contain:
      """
      core/categories
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

    # Verify dynamic count is a number
    When I run `wp block type list --dynamic --format=count`
    Then STDOUT should be a number

    When I run `wp block type list --format=count`
    Then STDOUT should be a number

  @require-wp-5.5
  Scenario: Filter by static blocks
    Given a WP install

    # Static blocks don't have render_callback, like core/paragraph
    # Static blocks are only registered server-side since WP 5.5
    When I run `wp block type list --static --field=name`
    Then STDOUT should contain:
      """
      core/paragraph
      """

    When I run `wp block type list --static --format=count`
    Then STDOUT should be a number

    # Verify static block has is_dynamic=false
    When I run `wp block type get core/paragraph --format=json`
    Then STDOUT should be JSON containing:
      """
      {"is_dynamic":false}
      """

  @require-wp-5.0
  Scenario: Get a specific block type
    Given a WP install

    When I run `wp block type get core/archives --format=json`
    Then STDOUT should be JSON containing:
      """
      {"name":"core/archives"}
      """

    When I run `wp block type get core/archives --field=name`
    Then STDOUT should be:
      """
      core/archives
      """

    # Verify is_dynamic is accessible (dynamic blocks return true)
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

  @require-wp-5.0
  Scenario: Filter by non-existent namespace returns empty
    Given a WP install

    When I run `wp block type list --namespace=nonexistent --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.0
  Scenario: List block types in various formats
    Given a WP install

    When I run `wp block type list --fields=name --format=table`
    Then STDOUT should be a table containing rows:
      | name           |
      | core/archives  |

    When I run `wp block type list --format=csv`
    Then STDOUT should contain:
      """
      name,
      """

    When I run `wp block type list --format=yaml`
    Then STDOUT should contain:
      """
      name: core/archives
      """

  @require-wp-5.0
  Scenario: List block types with custom fields
    Given a WP install

    When I run `wp block type list --fields=name,is_dynamic --format=table`
    Then STDOUT should be a table containing rows:
      | name           | is_dynamic |
      | core/archives  | 1          |

  @require-wp-5.0
  Scenario: Error when using both --dynamic and --static flags
    Given a WP install

    When I try `wp block type list --dynamic --static`
    Then STDERR should contain:
      """
      --dynamic and --static are mutually exclusive
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Check if a block type exists
    Given a WP install

    When I run `wp block type exists core/paragraph`
    Then STDOUT should be:
      """
      Success: Block type 'core/paragraph' is registered.
      """
    And the return code should be 0

    When I try `wp block type exists core/nonexistent`
    Then STDOUT should be empty
    And the return code should be 1
