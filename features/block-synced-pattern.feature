Feature: Synced pattern (wp_block) CRUD commands

  @require-wp-5.0
  Scenario: Create and manage synced patterns
    Given a WP install

    When I run `wp block synced-pattern create --title="Test Pattern" --content='<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_ID}

    When I run `wp block synced-pattern get {PATTERN_ID} --field=post_title`
    Then STDOUT should be:
      """
      Test Pattern
      """

    When I run `wp block synced-pattern get {PATTERN_ID} --field=sync_status`
    Then STDOUT should be:
      """
      synced
      """

    When I run `wp block synced-pattern get {PATTERN_ID} --format=json`
    Then STDOUT should be JSON containing:
      """
      {"post_title":"Test Pattern"}
      """

    When I run `wp block synced-pattern list --format=ids`
    Then STDOUT should contain:
      """
      {PATTERN_ID}
      """

    When I run `wp block synced-pattern update {PATTERN_ID} --title="Updated Pattern"`
    Then STDOUT should contain:
      """
      Updated
      """

    When I run `wp block synced-pattern get {PATTERN_ID} --field=post_title`
    Then STDOUT should be:
      """
      Updated Pattern
      """

    When I run `wp block synced-pattern delete {PATTERN_ID} --force`
    Then STDOUT should contain:
      """
      Deleted
      """

    When I try `wp block synced-pattern get {PATTERN_ID}`
    Then STDERR should contain:
      """
      not found
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Create unsynced pattern
    Given a WP install

    When I run `wp block synced-pattern create --title="Unsynced Pattern" --content='<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->' --sync-status=unsynced --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_ID}

    When I run `wp block synced-pattern get {PATTERN_ID} --field=sync_status`
    Then STDOUT should be:
      """
      unsynced
      """

    When I run `wp block synced-pattern delete {PATTERN_ID} --force`
    Then STDOUT should contain:
      """
      Deleted
      """

  @require-wp-5.0
  Scenario: Filter by sync status
    Given a WP install

    When I run `wp block synced-pattern create --title="Synced A" --content='<!-- wp:paragraph --><p>A</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {SYNCED_ID}

    When I run `wp block synced-pattern create --title="Unsynced B" --content='<!-- wp:paragraph --><p>B</p><!-- /wp:paragraph -->' --sync-status=unsynced --porcelain`
    Then save STDOUT as {UNSYNCED_ID}

    When I run `wp block synced-pattern list --sync-status=synced --format=ids`
    Then STDOUT should contain:
      """
      {SYNCED_ID}
      """
    And STDOUT should not contain:
      """
      {UNSYNCED_ID}
      """

    When I run `wp block synced-pattern list --sync-status=unsynced --format=ids`
    Then STDOUT should contain:
      """
      {UNSYNCED_ID}
      """
    And STDOUT should not contain:
      """
      {SYNCED_ID}
      """

    When I run `wp block synced-pattern delete {SYNCED_ID} {UNSYNCED_ID} --force`
    Then STDOUT should contain:
      """
      Deleted
      """

  @require-wp-5.0
  Scenario: Trash vs permanent delete
    Given a WP install

    When I run `wp block synced-pattern create --title="Trash Test" --content='<!-- wp:paragraph --><p>X</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {PATTERN_ID}

    # Delete without --force sends to trash
    When I run `wp block synced-pattern delete {PATTERN_ID}`
    Then STDOUT should contain:
      """
      Trashed
      """

    # Pattern should no longer appear in normal list
    When I run `wp block synced-pattern list --format=ids`
    Then STDOUT should not contain:
      """
      {PATTERN_ID}
      """

  @require-wp-5.0
  Scenario: Update content and sync status
    Given a WP install

    When I run `wp block synced-pattern create --title="Update Test" --content='<!-- wp:paragraph --><p>Original</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {PATTERN_ID}

    # Update content
    When I run `wp block synced-pattern update {PATTERN_ID} --content='<!-- wp:paragraph --><p>Modified</p><!-- /wp:paragraph -->'`
    Then STDOUT should contain:
      """
      Updated
      """

    When I run `wp block synced-pattern get {PATTERN_ID} --field=post_content`
    Then STDOUT should contain:
      """
      Modified
      """

    # Change from synced to unsynced
    When I run `wp block synced-pattern update {PATTERN_ID} --sync-status=unsynced`
    Then STDOUT should contain:
      """
      Updated
      """

    When I run `wp block synced-pattern get {PATTERN_ID} --field=sync_status`
    Then STDOUT should be:
      """
      unsynced
      """

    # Cleanup
    When I run `wp block synced-pattern delete {PATTERN_ID} --force`
    Then STDOUT should contain:
      """
      Deleted
      """

  @require-wp-5.0
  Scenario: Create pattern without title fails
    Given a WP install

    When I try `wp block synced-pattern create --content='<!-- wp:paragraph --><p>No title</p><!-- /wp:paragraph -->'`
    Then STDERR should contain:
      """
      title is required
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Search patterns by title
    Given a WP install

    When I run `wp block synced-pattern create --title="Alpha Hero Section" --content='<!-- wp:paragraph --><p>Alpha</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {ALPHA_ID}

    When I run `wp block synced-pattern create --title="Beta Footer" --content='<!-- wp:paragraph --><p>Beta</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {BETA_ID}

    When I run `wp block synced-pattern list --search=Hero --format=ids`
    Then STDOUT should contain:
      """
      {ALPHA_ID}
      """
    And STDOUT should not contain:
      """
      {BETA_ID}
      """

    # Cleanup
    When I run `wp block synced-pattern delete {ALPHA_ID} {BETA_ID} --force`
    Then STDOUT should contain:
      """
      Deleted
      """

  @require-wp-5.0
  Scenario: Get non-existent pattern
    Given a WP install

    When I try `wp block synced-pattern get 999999`
    Then STDERR should contain:
      """
      not found
      """
    And the return code should be 1

  @require-wp-5.0
  Scenario: Delete multiple patterns including non-existent
    Given a WP install

    When I run `wp block synced-pattern create --title="To Delete" --content='<!-- wp:paragraph --><p>X</p><!-- /wp:paragraph -->' --porcelain`
    Then save STDOUT as {VALID_ID}

    # Trying to delete both valid and invalid IDs
    When I try `wp block synced-pattern delete {VALID_ID} 999999 --force`
    Then STDOUT should contain:
      """
      Deleted 1
      """
    And STDERR should contain:
      """
      not found
      """
