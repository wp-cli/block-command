@require-wp-5.0
Feature: Search posts by block usage

  Background:
    Given a WP install

  Scenario: Search posts by block type usage
    Given a quote-post.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>Hello world</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create quote-post.html --post_type=post --post_title='Quote Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {QUOTE_POST_ID}

    When I run `wp block search --block=core/quote --post_type=post --field=ID`
    Then STDOUT should be:
      """
      {QUOTE_POST_ID}
      """

  Scenario: Search posts by block namespace
    Given a core-namespace-post.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>Hello core</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create core-namespace-post.html --post_type=post --post_title='Core Namespace Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CORE_NAMESPACE_POST_ID}

    When I run `wp block search --block-namespace=core --post_type=post --field=ID`
    Then STDOUT should contain:
      """
      {CORE_NAMESPACE_POST_ID}
      """

  Scenario: Search posts by custom block namespace
    Given a custom-namespace-post.html file:
      """
      <!-- wp:my-plugin/card --><div class="wp-block-my-plugin-card">Hello custom</div><!-- /wp:my-plugin/card -->
      """
    When I run `wp post create custom-namespace-post.html --post_type=post --post_title='Custom Namespace Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CUSTOM_NAMESPACE_POST_ID}

    When I run `wp block search --block-namespace=my-plugin --post_type=post --field=ID`
    Then STDOUT should be:
      """
      {CUSTOM_NAMESPACE_POST_ID}
      """

  Scenario: Search nested block usage
    Given a nested-image-post.html file:
      """
      <!-- wp:group --><div class="wp-block-group"><!-- wp:image {"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group -->
      """
    When I run `wp post create nested-image-post.html --post_type=page --post_title='Nested Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NESTED_POST_ID}

    When I run `wp block search --block=core/image --post_type=page --field=ID`
    Then STDOUT should be:
      """
      {NESTED_POST_ID}
      """

  Scenario: Search block usage by style
    Given a rounded-image-post.html file:
      """
      <!-- wp:image {"className":"is-style-rounded"} --><figure class="wp-block-image is-style-rounded"><img alt="" /></figure><!-- /wp:image -->
      """
    When I run `wp post create rounded-image-post.html --post_type=post --post_title='Rounded Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ROUNDED_IMAGE_POST_ID}

    Given a plain-image-post.html file:
      """
      <!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image -->
      """
    When I run `wp post create plain-image-post.html --post_type=post --post_title='Plain Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number

    Given a rounded-quote-post.html file:
      """
      <!-- wp:quote {"className":"is-style-rounded"} --><blockquote class="wp-block-quote is-style-rounded"><p>Hello quote</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create rounded-quote-post.html --post_type=post --post_title='Rounded Quote Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ROUNDED_QUOTE_POST_ID}

    When I run `wp block search --block=core/image --style=rounded --field=ID`
    Then STDOUT should be:
      """
      {ROUNDED_IMAGE_POST_ID}
      """

    When I run `wp block search --style=rounded --field=ID`
    Then STDOUT should contain:
      """
      {ROUNDED_IMAGE_POST_ID}
      """

    And STDOUT should contain:
      """
      {ROUNDED_QUOTE_POST_ID}
      """

  Scenario: Style search ignores plain text false positives
    When I run `wp post create --post_type=post --post_title='Style Token Plain Text Post' --post_status=publish --post_content='This post mentions is-style-rounded in plain text only.' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {STYLE_TOKEN_TEXT_POST_ID}

    When I run `wp block search --style=rounded --field=ID --format=ids`
    Then STDOUT should not contain:
      """
      {STYLE_TOKEN_TEXT_POST_ID}
      """

  Scenario: Search block usage count
    Given a counted-quote-one.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>One</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create counted-quote-one.html --post_type=post --post_title='Counted Quote One' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {COUNTED_QUOTE_ONE_ID}

    Given a counted-quote-two.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>Two</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create counted-quote-two.html --post_type=post --post_title='Counted Quote Two' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {COUNTED_QUOTE_TWO_ID}

    When I run `wp block search --block=core/quote --post_type=post --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp block search --block=core/quote --post__in={COUNTED_QUOTE_ONE_ID},{COUNTED_QUOTE_TWO_ID} --format=ids`
    Then STDOUT should contain:
      """
      {COUNTED_QUOTE_ONE_ID}
      """

    And STDOUT should contain:
      """
      {COUNTED_QUOTE_TWO_ID}
      """

  Scenario: Search block usage with selected fields in JSON
    Given a double-quote-post.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>One</p></blockquote><!-- /wp:quote --><!-- wp:quote --><blockquote class="wp-block-quote"><p>Two</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create double-quote-post.html --post_type=post --post_title='Double Quote Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {DOUBLE_QUOTE_POST_ID}

    When I run `wp block search --block=core/quote --post__in={DOUBLE_QUOTE_POST_ID} --fields=ID,occurrences --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"ID":{DOUBLE_QUOTE_POST_ID},"occurrences":2}]
      """

  Scenario: Search block usage with WP_Query pagination arguments
    Given a paged-first-post.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>First paged match</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create paged-first-post.html --post_type=post --post_title='A Paged Match' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PAGED_FIRST_POST_ID}

    Given a paged-second-post.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>Second paged match</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create paged-second-post.html --post_type=post --post_title='B Paged Match' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PAGED_SECOND_POST_ID}

    When I run `wp block search --block=core/quote --post_type=post --orderby=title --order=ASC --posts_per_page=1 --paged=1 --field=ID`
    Then STDOUT should be:
      """
      {PAGED_FIRST_POST_ID}
      """

    When I run `wp block search --block=core/quote --post_type=post --orderby=title --order=ASC --posts_per_page=1 --paged=2 --field=ID`
    Then STDOUT should be:
      """
      {PAGED_SECOND_POST_ID}
      """

  Scenario: Search blocks embedded from a specific pattern
    Given a pattern-rsvp-post.html file:
      """
      <!-- wp:group {"metadata":{"categories":["call-to-action"],"patternName":"twentytwentyfive/event-rsvp","name":"Event RSVP"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>RSVP now</p><!-- /wp:paragraph --></div><!-- /wp:group -->
      """
    When I run `wp post create pattern-rsvp-post.html --post_type=post --post_title='Pattern RSVP Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_RSVP_POST_ID}

    Given a pattern-header-post.html file:
      """
      <!-- wp:group {"metadata":{"categories":["header"],"patternName":"twentytwentyfive/site-header","name":"Site Header"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>Header block</p><!-- /wp:paragraph --></div><!-- /wp:group -->
      """
    When I run `wp post create pattern-header-post.html --post_type=post --post_title='Pattern Header Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_HEADER_POST_ID}

    Given a spaced-pattern-rsvp-post.html file:
      """
      <!-- wp:group {"metadata":{"categories":["call-to-action"],"patternName": "twentytwentyfive/event-rsvp","name":"Event RSVP Spaced"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>RSVP later</p><!-- /wp:paragraph --></div><!-- /wp:group -->
      """
    When I run `wp post create spaced-pattern-rsvp-post.html --post_type=post --post_title='Pattern RSVP Spaced Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SPACED_PATTERN_RSVP_POST_ID}

    When I run `wp block search --pattern=twentytwentyfive/event-rsvp --field=ID`
    Then STDOUT should contain:
      """
      {PATTERN_RSVP_POST_ID}
      """

    And STDOUT should contain:
      """
      {SPACED_PATTERN_RSVP_POST_ID}
      """

    When I run `wp block search --pattern=twentytwentyfive/event-rsvp --field=ID --format=ids`
    Then STDOUT should not contain:
      """
      {PATTERN_HEADER_POST_ID}
      """

    When I run `wp block search --pattern-namespace=twentytwentyfive --field=ID`
    Then STDOUT should contain:
      """
      {PATTERN_RSVP_POST_ID}
      """

    And STDOUT should contain:
      """
      {SPACED_PATTERN_RSVP_POST_ID}
      """

    And STDOUT should contain:
      """
      {PATTERN_HEADER_POST_ID}
      """

  Scenario: Search pattern blocks with an additional block filter
    Given a patterned-image-post.html file:
      """
      <!-- wp:group {"metadata":{"categories":["media"],"patternName":"twentytwentyfive/media-highlight","name":"Media Highlight"}} --><div class="wp-block-group"><!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group -->
      """
    When I run `wp post create patterned-image-post.html --post_type=post --post_title='Patterned Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_IMAGE_POST_ID}

    When I run `wp block search --pattern-namespace=twentytwentyfive --block=core/image --field=ID`
    Then STDOUT should be:
      """
      {PATTERN_IMAGE_POST_ID}
      """

  Scenario: Search uses the nearest pattern ancestor for nested patterns
    Given a nested-pattern-image-post.html file:
      """
      <!-- wp:group {"metadata":{"categories":["outer"],"patternName":"twentytwentyfive/outer-shell","name":"Outer Shell"}} --><div class="wp-block-group"><!-- wp:group {"metadata":{"categories":["inner"],"patternName":"twentytwentyfive/inner-media","name":"Inner Media"}} --><div class="wp-block-group"><!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group --></div><!-- /wp:group -->
      """
    When I run `wp post create nested-pattern-image-post.html --post_type=post --post_title='Nested Pattern Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NESTED_PATTERN_IMAGE_POST_ID}

    When I run `wp block search --pattern=twentytwentyfive/inner-media --block=core/image --field=ID`
    Then STDOUT should be:
      """
      {NESTED_PATTERN_IMAGE_POST_ID}
      """

    When I run `wp block search --pattern=twentytwentyfive/outer-shell --block=core/image --field=ID --format=ids`
    Then STDOUT should not contain:
      """
      {NESTED_PATTERN_IMAGE_POST_ID}
      """

  Scenario: Search posts by synced pattern reference
    Given a pattern.html file:
      """
      <!-- wp:paragraph --><p>Reusable</p><!-- /wp:paragraph -->
      """
    When I run `wp block synced-pattern create pattern.html --title='Reusable Search Pattern' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SYNCED_PATTERN_ID}

    When I run `wp post create --post_type=post --post_title='Reusable Pattern Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SYNCED_PATTERN_POST_ID}

    When I run `wp post block insert {SYNCED_PATTERN_POST_ID} core/block --attrs='{"ref":{SYNCED_PATTERN_ID}}'`
    Then STDOUT should contain:
      """
      Success: Inserted block into post {SYNCED_PATTERN_POST_ID}.
      """

    Given a different-reusable-pattern-post.html file:
      """
      <!-- wp:quote --><blockquote class="wp-block-quote"><p>Not reusable</p></blockquote><!-- /wp:quote -->
      """
    When I run `wp post create different-reusable-pattern-post.html --post_type=post --post_title='Different Reusable Pattern Post' --post_status=publish --porcelain`
    Then STDOUT should be a number

    When I run `wp block search --synced-pattern={SYNCED_PATTERN_ID} --field=ID`
    Then STDOUT should contain:
      """
      {SYNCED_PATTERN_POST_ID}
      """

  Scenario: Pattern and pattern namespace are mutually exclusive
    When I try `wp block search --pattern=twentytwentyfive/event-rsvp --pattern-namespace=twentytwentyfive`
    Then STDERR should contain:
      """
      The --pattern and --pattern-namespace parameters are mutually exclusive.
      """
    And the return code should be 1

  Scenario: Block name and namespace are mutually exclusive
    When I try `wp block search --block=core/quote --block-namespace=core`
    Then STDERR should contain:
      """
      The --block and --block-namespace parameters are mutually exclusive.
      """
    And the return code should be 1

  Scenario: Synced pattern parameter must be a positive integer
    When I try `wp block search --synced-pattern=0`
    Then STDERR should contain:
      """
      The --synced-pattern parameter must be a positive integer post ID.
      """
    And the return code should be 1

  Scenario: Search requires at least one filter
    When I try `wp block search`
    Then STDERR should contain:
      """
      At least one block filter is required: --block, --block-namespace, --style, --pattern, --pattern-namespace, or --synced-pattern.
      """
    And the return code should be 1
