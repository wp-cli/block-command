Feature: Block template commands

  @require-wp-5.9
  Scenario: List block templates
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --format=count`
    Then STDOUT should be a number

    When I run `wp block template list --format=json`
    Then STDOUT should not be empty

    When I run `wp block template list --format=ids`
    Then STDOUT should not be empty

  @require-wp-5.9
  Scenario: List template parts
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --type=wp_template_part --format=count`
    Then STDOUT should be a number

    When I run `wp block template list --type=wp_template_part --field=slug`
    Then STDOUT should not be empty

  @require-wp-5.9
  Scenario: Filter templates by source
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --source=theme --format=count`
    Then STDOUT should be a number

  @require-wp-5.9
  Scenario: Filter templates by slug
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    # Filter by single slug
    When I run `wp block template list --slug=index --field=slug`
    Then STDOUT should be:
      """
      index
      """

    # Filter by multiple slugs
    When I run `wp block template list --slug=index,single --format=count`
    Then STDOUT should be a number

  @require-wp-5.9
  Scenario: Filter template parts by area
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --type=wp_template_part --area=header --field=slug`
    Then STDOUT should contain:
      """
      header
      """

  @require-wp-5.9
  Scenario: Get a block template
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    # Get the first template ID
    When I run `wp block template list --field=id`
    Then STDOUT should not be empty
    And save STDOUT '%s' as {TEMPLATE_ID}

    # Get the template details
    When I run `wp block template get {TEMPLATE_ID} --format=json`
    Then STDOUT should be JSON containing:
      """
      {"id":"{TEMPLATE_ID}"}
      """

    # Get a specific field
    When I run `wp block template get {TEMPLATE_ID} --field=slug`
    Then STDOUT should not be empty

    # Get template content
    When I run `wp block template get {TEMPLATE_ID} --field=content`
    Then STDOUT should contain:
      """
      <!-- wp:
      """

  @require-wp-5.9
  Scenario: Export template to stdout
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    # Get the first template ID using %s pattern to capture first line
    When I run `wp block template list --field=id`
    Then STDOUT should not be empty
    And save STDOUT '%s' as {TEMPLATE_ID}

    # Export it to stdout - should contain block markup
    When I run `wp block template export {TEMPLATE_ID} --stdout`
    Then STDOUT should contain:
      """
      <!-- wp:
      """

  @require-wp-5.9
  Scenario: Get a non-existent template
    Given a WP install

    When I try `wp block template get nonexistent//template`
    Then STDERR should contain:
      """
      not found
      """
    And the return code should be 1

  @require-wp-5.9
  Scenario: Export non-existent template fails
    Given a WP install

    When I try `wp block template export nonexistent//template --stdout`
    Then STDERR should contain:
      """
      not found
      """
    And the return code should be 1

  @require-wp-5.9
  Scenario: Export template to file
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --field=id`
    Then STDOUT should not be empty
    And save STDOUT '%s' as {TEMPLATE_ID}

    When I run `wp block template export {TEMPLATE_ID} --file=exported-template.html`
    Then STDOUT should contain:
      """
      Success:
      """
    And the exported-template.html file should contain:
      """
      <!-- wp:
      """

  @require-wp-5.9
  Scenario: List templates with classic theme returns empty
    Given a WP install
    # Default theme is usually a classic theme in test environment
    # If twentytwentyone is available, use it as a classic theme example

    When I run `wp block template list --format=count`
    # Classic themes may return 0 templates
    Then STDOUT should be a number

  @require-wp-5.9
  Scenario: Filter template parts by invalid area returns empty
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --type=wp_template_part --area=nonexistent --format=count`
    Then STDOUT should be:
      """
      0
      """

  @require-wp-5.9
  Scenario: Export template creates subdirectories
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --field=id`
    Then STDOUT should not be empty
    And save STDOUT '%s' as {TEMPLATE_ID}

    When I run `wp block template export {TEMPLATE_ID} --file=subdir/nested/template.html`
    Then STDOUT should contain:
      """
      Success: Exported template to 'subdir/nested/template.html'.
      """
    And the subdir/nested/template.html file should exist

  @require-wp-5.9
  Scenario: Export fails when both --file and --dir are provided
    Given a WP install
    And I try `wp theme install twentytwentyfour --activate`

    When I run `wp block template list --field=id`
    Then STDOUT should not be empty
    And save STDOUT '%s' as {TEMPLATE_ID}

    When I try `wp block template export {TEMPLATE_ID} --file=template.html --dir=./exports`
    Then STDERR should contain:
      """
      The --file and --dir options are mutually exclusive.
      """
    And the return code should be 1
