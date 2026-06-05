@require-wp-5.0
Feature: Search posts by block usage

  Background:
    Given a WP install

  Scenario: Search posts by block type usage
    When I run `wp post --post_type=post create --post_title='Quote Post' --post_status=publish --post_content='<!-- wp:quote --><blockquote class="wp-block-quote"><p>Hello world</p></blockquote><!-- /wp:quote -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {QUOTE_POST_ID}

    When I run `wp block search --block=core/quote --post_type=post --field=ID`
    Then STDOUT should be:
      """
      {QUOTE_POST_ID}
      """

  Scenario: Search posts by block namespace
    When I run `wp post --post_type=post create --post_title='Core Namespace Post' --post_status=publish --post_content='<!-- wp:quote --><blockquote class="wp-block-quote"><p>Hello core</p></blockquote><!-- /wp:quote -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {CORE_NAMESPACE_POST_ID}

    When I run `wp block search --block-namespace=core --post_type=post --field=ID`
    Then STDOUT should contain:
      """
      {CORE_NAMESPACE_POST_ID}
      """

  Scenario: Search nested block usage
    When I run `wp post --post_type=page create --post_title='Nested Image Post' --post_status=publish --post_content='<!-- wp:group --><div class="wp-block-group"><!-- wp:image {"sizeSlug":"large"} --><figure class="wp-block-image size-large"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NESTED_POST_ID}

    When I run `wp block search --block=core/image --post_type=page --field=ID`
    Then STDOUT should be:
      """
      {NESTED_POST_ID}
      """

  Scenario: Search block usage by style
    When I run `wp post --post_type=post create --post_title='Rounded Image Post' --post_status=publish --post_content='<!-- wp:image {"className":"is-style-rounded"} --><figure class="wp-block-image is-style-rounded"><img alt="" /></figure><!-- /wp:image -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {ROUNDED_IMAGE_POST_ID}

    When I run `wp post --post_type=post create --post_title='Plain Image Post' --post_status=publish --post_content='<!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image -->' --porcelain`
    Then STDOUT should be a number

    When I run `wp post --post_type=post create --post_title='Rounded Quote Post' --post_status=publish --post_content='<!-- wp:quote {"className":"is-style-rounded"} --><blockquote class="wp-block-quote is-style-rounded"><p>Hello quote</p></blockquote><!-- /wp:quote -->' --porcelain`
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

  Scenario: Search block usage count
    When I run `wp post --post_type=post create --post_title='Counted Quote One' --post_status=publish --post_content='<!-- wp:quote --><blockquote class="wp-block-quote"><p>One</p></blockquote><!-- /wp:quote -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {COUNTED_QUOTE_ONE_ID}

    When I run `wp post --post_type=post create --post_title='Counted Quote Two' --post_status=publish --post_content='<!-- wp:quote --><blockquote class="wp-block-quote"><p>Two</p></blockquote><!-- /wp:quote -->' --porcelain`
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
    When I run `wp post --post_type=post create --post_title='Double Quote Post' --post_status=publish --post_content='<!-- wp:quote --><blockquote class="wp-block-quote"><p>One</p></blockquote><!-- /wp:quote --><!-- wp:quote --><blockquote class="wp-block-quote"><p>Two</p></blockquote><!-- /wp:quote -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {DOUBLE_QUOTE_POST_ID}

    When I run `wp block search --block=core/quote --post__in={DOUBLE_QUOTE_POST_ID} --fields=ID,occurrences --format=json`
    Then STDOUT should be JSON containing:
      """
      [{"ID":{DOUBLE_QUOTE_POST_ID},"occurrences":2}]
      """

  Scenario: Search blocks embedded from a specific pattern
    When I run `wp post --post_type=post create --post_title='Pattern RSVP Post' --post_status=publish --post_content='<!-- wp:group {"metadata":{"categories":["call-to-action"],"patternName":"twentytwentyfive/event-rsvp","name":"Event RSVP"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>RSVP now</p><!-- /wp:paragraph --></div><!-- /wp:group -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_RSVP_POST_ID}

    When I run `wp post --post_type=post create --post_title='Pattern Header Post' --post_status=publish --post_content='<!-- wp:group {"metadata":{"categories":["header"],"patternName":"twentytwentyfive/site-header","name":"Site Header"}} --><div class="wp-block-group"><!-- wp:paragraph --><p>Header block</p><!-- /wp:paragraph --></div><!-- /wp:group -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_HEADER_POST_ID}

    When I run `wp block search --pattern=twentytwentyfive/event-rsvp --field=ID`
    Then STDOUT should be:
      """
      {PATTERN_RSVP_POST_ID}
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
      {PATTERN_HEADER_POST_ID}
      """

  Scenario: Search pattern blocks with an additional block filter
    When I run `wp post --post_type=post create --post_title='Patterned Image Post' --post_status=publish --post_content='<!-- wp:group {"metadata":{"categories":["media"],"patternName":"twentytwentyfive/media-highlight","name":"Media Highlight"}} --><div class="wp-block-group"><!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group -->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_IMAGE_POST_ID}

    When I run `wp block search --pattern-namespace=twentytwentyfive --block=core/image --field=ID`
    Then STDOUT should be:
      """
      {PATTERN_IMAGE_POST_ID}
      """

  Scenario: Search uses the nearest pattern ancestor for nested patterns
    When I run `wp post --post_type=post create --post_title='Nested Pattern Image Post' --post_status=publish --post_content='<!-- wp:group {"metadata":{"categories":["outer"],"patternName":"twentytwentyfive/outer-shell","name":"Outer Shell"}} --><div class="wp-block-group"><!-- wp:group {"metadata":{"categories":["inner"],"patternName":"twentytwentyfive/inner-media","name":"Inner Media"}} --><div class="wp-block-group"><!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group --></div><!-- /wp:group -->' --porcelain`
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

    When I run `wp post --post_type=post create --post_title='Reusable Pattern Post' --post_status=publish --post_content='<!-- wp:block {"ref":{SYNCED_PATTERN_ID}} /-->' --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SYNCED_PATTERN_POST_ID}

    When I run `wp post --post_type=post create --post_title='Different Reusable Pattern Post' --post_status=publish --post_content='<!-- wp:quote --><blockquote class="wp-block-quote"><p>Not reusable</p></blockquote><!-- /wp:quote -->' --porcelain`
    Then STDOUT should be a number

    When I run `wp block search --synced-pattern={SYNCED_PATTERN_ID} --field=ID`
    Then STDOUT should be:
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
