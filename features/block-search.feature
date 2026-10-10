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

  Scenario: Pagination without orderby is stable for posts sharing a date
    When I run `wp eval 'foreach ( [ 1, 2, 3 ] as $i ) { wp_insert_post( [ "post_title" => "Same date $i", "post_status" => "publish", "post_date" => "2024-01-01 00:00:00", "post_content" => "<!-- wp:my-plugin/stable /-->" ] ); } echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --block=my-plugin/stable --post_type=post --field=ID`
    Then save STDOUT as {ALL_IDS}

    When I run `wp block search --block=my-plugin/stable --post_type=post --posts_per_page=1 --paged=1 --field=ID`
    Then save STDOUT as {PAGE_ONE_ID}

    When I run `wp block search --block=my-plugin/stable --post_type=post --posts_per_page=1 --paged=2 --field=ID`
    Then save STDOUT as {PAGE_TWO_ID}

    When I run `wp block search --block=my-plugin/stable --post_type=post --posts_per_page=1 --paged=3 --field=ID`
    Then save STDOUT as {PAGE_THREE_ID}

    When I run `wp block search --block=my-plugin/stable --post_type=post --post__in={PAGE_ONE_ID},{PAGE_TWO_ID},{PAGE_THREE_ID} --format=count`
    Then STDOUT should be:
      """
      3
      """

  Scenario: Block search without occurrences does not match blocks sharing a name prefix
    When I run `wp eval 'wp_insert_post( [ "post_title" => "Exact", "post_status" => "publish", "post_content" => "<!-- wp:my-plugin/prefix /-->" ] ); wp_insert_post( [ "post_title" => "Prefix only", "post_status" => "publish", "post_content" => "<!-- wp:my-plugin/prefix-extra /-->" ] ); echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --block=my-plugin/prefix --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Exact
      """

    When I run `wp block search --block=my-plugin/prefix --post_type=post --fields=post_title,occurrences --format=csv`
    Then STDOUT should be:
      """
      post_title,occurrences
      Exact,1
      """

  Scenario: Offset without a page size skips posts and returns the rest
    When I run `wp eval 'foreach ( [ 1, 2, 3 ] as $i ) { wp_insert_post( [ "post_title" => "Offset $i", "post_status" => "publish", "post_content" => "<!-- wp:my-plugin/offset /-->" ] ); } echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --block=my-plugin/offset --post_type=post --offset=1 --format=count`
    Then STDOUT should be:
      """
      2
      """

  Scenario: Prefilter finds explicitly namespaced core blocks and spaced JSON attributes
    When I run `wp eval 'wp_insert_post( [ "post_title" => "Explicit core", "post_status" => "publish", "post_content" => "<!-- wp:core/image /-->" ] ); wp_insert_post( [ "post_title" => "Spaced ref", "post_status" => "publish", "post_content" => "<!-- wp:block {\"ref\": 4242} /-->" ] ); wp_insert_post( [ "post_title" => "Spaced pattern", "post_status" => "publish", "post_content" => "<!-- wp:paragraph {\"metadata\":{\"patternName\": \"my-theme/spaced\"}} --><p>x</p><!-- /wp:paragraph -->" ] ); echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --block=core/image --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Explicit core
      """

    When I run `wp block search --synced-pattern=4242 --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Spaced ref
      """

    When I run `wp block search --pattern=my-theme/spaced --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Spaced pattern
      """

  Scenario: Prefilter finds pattern and synced pattern attributes with unusual JSON spacing
    When I run `wp eval 'wp_insert_post( [ "post_title" => "Wide ref", "post_status" => "publish", "post_content" => "<!-- wp:block {\"ref\"   :   5151} /-->" ] ); wp_insert_post( [ "post_title" => "Other ref", "post_status" => "publish", "post_content" => "<!-- wp:block {\"ref\":51510} /-->" ] ); wp_insert_post( [ "post_title" => "Wide pattern", "post_status" => "publish", "post_content" => "<!-- wp:paragraph {\"metadata\":{\"patternName\"  :  \"my-theme/wide\"}} --><p>x</p><!-- /wp:paragraph -->" ] ); echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --synced-pattern=5151 --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Wide ref
      """

    When I run `wp block search --pattern=my-theme/wide --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Wide pattern
      """

    When I run `wp block search --pattern-namespace=my-theme --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Wide pattern
      """

  Scenario: Style names that are escaped when serialized are still found
    When I run `wp eval 'wp_insert_post( wp_slash( [ "post_title" => "Escaped style", "post_status" => "publish", "post_content" => serialize_block( [ "blockName" => "core/paragraph", "attrs" => [ "className" => "is-style-a&b" ], "innerBlocks" => [], "innerHTML" => "<p class=\"is-style-a&amp;b\">x</p>", "innerContent" => [ "<p class=\"is-style-a&amp;b\">x</p>" ] ] ) ] ) ); echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --style='a&b' --post_type=post --field=post_title`
    Then STDOUT should be:
      """
      Escaped style
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
      <!-- wp:group --><div class="wp-block-group"><!-- wp:image {"metadata":{"categories":["media"],"patternName":"twentytwentyfive/media-highlight","name":"Media Highlight"}} --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group -->
      """
    When I run `wp post create patterned-image-post.html --post_type=post --post_title='Patterned Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {PATTERN_IMAGE_POST_ID}

    When I run `wp block search --pattern-namespace=twentytwentyfive --block=core/image --field=ID`
    Then STDOUT should be:
      """
      {PATTERN_IMAGE_POST_ID}
      """

  Scenario: Pattern search counts only blocks carrying the pattern metadata
    Given a nested-pattern-image-post.html file:
      """
      <!-- wp:group {"metadata":{"categories":["outer"],"patternName":"twentytwentyfive/outer-shell","name":"Outer Shell"}} --><div class="wp-block-group"><!-- wp:group {"metadata":{"categories":["inner"],"patternName":"twentytwentyfive/inner-media","name":"Inner Media"}} --><div class="wp-block-group"><!-- wp:image --><figure class="wp-block-image"><img alt="" /></figure><!-- /wp:image --></div><!-- /wp:group --></div><!-- /wp:group -->
      """
    When I run `wp post create nested-pattern-image-post.html --post_type=post --post_title='Nested Pattern Image Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {NESTED_PATTERN_IMAGE_POST_ID}

    When I run `wp block search --pattern=twentytwentyfive/inner-media --field=occurrences --post__in={NESTED_PATTERN_IMAGE_POST_ID}`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp block search --pattern-namespace=twentytwentyfive --field=occurrences --post__in={NESTED_PATTERN_IMAGE_POST_ID}`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp block search --pattern=twentytwentyfive/inner-media --block=core/image --field=ID --format=ids`
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

  Scenario: Synced pattern parameter rejects malformed IDs
    When I try `wp block search --synced-pattern=12abc`
    Then STDERR should contain:
      """
      The --synced-pattern parameter must be a positive integer post ID.
      """
    And the return code should be 1

  Scenario: Synced pattern cannot be combined with block filters
    When I try `wp block search --synced-pattern=5 --block=core/image`
    Then STDERR should contain:
      """
      The --synced-pattern parameter cannot be combined with --block or --block-namespace.
      """
    And the return code should be 1

    When I try `wp block search --synced-pattern=5 --block-namespace=core`
    Then STDERR should contain:
      """
      The --synced-pattern parameter cannot be combined with --block or --block-namespace.
      """
    And the return code should be 1

  Scenario: Limit stops after the requested number of matches
    When I run `wp eval 'foreach ( [ 1, 2, 3 ] as $i ) { wp_insert_post( [ "post_title" => "Limit $i", "post_status" => "publish", "post_content" => "<!-- wp:my-plugin/limit /-->" ] ); } echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --block=my-plugin/limit --post_type=post --limit=2 --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `wp block search --block=my-plugin/limit --post_type=post --limit=10 --format=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `wp block search --block=my-plugin/limit --post_type=post --limit=1 --field=post_title`
    Then STDOUT should be:
      """
      Limit 1
      """

  Scenario: Limit must be a positive integer and cannot be combined with paged
    When I try `wp block search --block=core/paragraph --limit=0`
    Then STDERR should contain:
      """
      The --limit parameter must be a positive integer.
      """
    And the return code should be 1

    When I try `wp block search --block=core/paragraph --limit=5x`
    Then STDERR should contain:
      """
      The --limit parameter must be a positive integer.
      """
    And the return code should be 1

    When I try `wp block search --block=core/paragraph --limit=5 --paged=2`
    Then STDERR should contain:
      """
      The --limit parameter cannot be combined with --paged.
      """
    And the return code should be 1

  Scenario: Block name without namespace is treated as a core block
    Given a short-name-post.html file:
      """
      <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->
      """
    When I run `wp post create short-name-post.html --post_type=post --post_title='Short Name Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {SHORT_NAME_POST_ID}

    When I run `wp block search --block=paragraph --post_type=post --field=ID`
    Then STDOUT should contain:
      """
      {SHORT_NAME_POST_ID}
      """

  Scenario: Permalink is only included when the url field is requested
    Given a url-post.html file:
      """
      <!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->
      """
    When I run `wp post create url-post.html --post_type=post --post_title='Url Post' --post_status=publish --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {URL_POST_ID}

    When I run `wp block search --block=core/paragraph --post__in={URL_POST_ID} --fields=ID,url --format=csv`
    Then STDOUT should contain:
      """
      {URL_POST_ID},http
      """

  Scenario: Search scans more posts than a single batch
    When I run `wp eval 'for ( $i = 0; $i < 205; $i++ ) { wp_insert_post( [ "post_title" => "Batch $i", "post_status" => "publish", "post_content" => "<!-- wp:my-plugin/batch /-->" ] ); } echo "done";'`
    Then STDOUT should be:
      """
      done
      """

    When I run `wp block search --block=my-plugin/batch --post_type=post --format=count`
    Then STDOUT should be:
      """
      205
      """
