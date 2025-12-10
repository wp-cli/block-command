wp-cli/block-command
====================

Manages block types, patterns, styles, bindings, and templates.

[![Testing](https://github.com/wp-cli/block-command/actions/workflows/testing.yml/badge.svg)](https://github.com/wp-cli/block-command/actions/workflows/testing.yml)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

This package implements the following commands:

### wp block

Manages WordPress block editor blocks and related entities.

~~~
wp block
~~~

This command provides tools for working with the WordPress block editor,
including block types, patterns, styles, bindings, templates, and synced patterns.

**EXAMPLES**

    # List all registered block types
    $ wp block type list

    # Get a specific block pattern
    $ wp block pattern get my-theme/hero

    # List block styles for a specific block
    $ wp block style list --block=core/button

    # Export a block template
    $ wp block template export twentytwentyfour//single --stdout

    # Create a synced pattern
    $ wp block synced-pattern create --title="My Pattern" --content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'



### wp block type

Retrieves details on registered block types.

~~~
wp block type
~~~

Get information on WordPress' built-in and custom block types from the
WP_Block_Type_Registry.

**EXAMPLES**

    # List all registered block types
    $ wp block type list

    # Get details about a specific block type
    $ wp block type get core/paragraph --format=json

    # List all core blocks
    $ wp block type list --namespace=core





### wp block type list

Lists registered block types.

~~~
wp block type list [--namespace=<namespace>] [--dynamic] [--static] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--namespace=<namespace>]
		Filter by block namespace (e.g., 'core', 'my-plugin').

	[--dynamic]
		Only show dynamic blocks (blocks with render_callback).

	[--static]
		Only show static blocks (blocks without render_callback).

	[--field=<field>]
		Prints the value of a single field for each block type.

	[--fields=<fields>]
		Limit the output to specific block type fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each block type:

* name
* title
* description
* category
* is_dynamic

These fields are optionally available:

* icon
* keywords
* parent
* ancestor
* supports
* attributes
* provides_context
* uses_context
* block_hooks
* editor_script_handles
* script_handles
* view_script_handles
* editor_style_handles
* style_handles
* view_style_handles
* api_version

**EXAMPLES**

    # List all registered block types
    $ wp block type list

    # List all core blocks
    $ wp block type list --namespace=core --fields=name,title,category

    # List only dynamic blocks
    $ wp block type list --dynamic --format=json

    # Get count of registered block types
    $ wp block type list --format=count



### wp block type get

Gets details about a registered block type.

~~~
wp block type get <name> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<name>
		Block type name (e.g., 'core/paragraph').

	[--field=<field>]
		Instead of returning the whole block type, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get details about the paragraph block
    $ wp block type get core/paragraph

    # Get the supports field as JSON
    $ wp block type get core/paragraph --field=supports --format=json

    # Get specific fields
    $ wp block type get core/image --fields=name,title,supports --format=json



### wp block pattern

Retrieves details on registered block patterns.

~~~
wp block pattern
~~~

Get information on WordPress' built-in and custom block patterns from the
WP_Block_Patterns_Registry.

**EXAMPLES**

    # List all registered block patterns
    $ wp block pattern list

    # Get details about a specific pattern
    $ wp block pattern get core/query-standard-posts --format=json

    # List patterns in a specific category
    $ wp block pattern list --category=featured





### wp block pattern list

Lists registered block patterns.

~~~
wp block pattern list [--category=<category>] [--search=<search>] [--inserter] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--category=<category>]
		Filter by pattern category.

	[--search=<search>]
		Search patterns by title or keywords.

	[--inserter]
		Only show patterns visible in the inserter.

	[--field=<field>]
		Prints the value of a single field for each pattern.

	[--fields=<fields>]
		Limit the output to specific pattern fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each pattern:

* name
* title
* description
* categories

These fields are optionally available:

* content
* keywords
* blockTypes
* postTypes
* templateTypes
* inserter
* viewportWidth

**EXAMPLES**

    # List all registered patterns
    $ wp block pattern list

    # List patterns in the 'buttons' category
    $ wp block pattern list --category=buttons

    # Search for hero patterns
    $ wp block pattern list --search=hero

    # Export all patterns to JSON
    $ wp block pattern list --format=json > patterns.json



### wp block pattern get

Gets details about a registered block pattern.

~~~
wp block pattern get <name> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<name>
		Pattern name including namespace (e.g., 'core/query-standard-posts').

	[--field=<field>]
		Instead of returning the whole pattern, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get a pattern's content
    $ wp block pattern get core/query-standard-posts --field=content

    # Get full pattern details as JSON
    $ wp block pattern get my-theme/hero --format=json



### wp block pattern-category

Retrieves details on registered block pattern categories.

~~~
wp block pattern-category
~~~

Get information on block pattern categories from the
WP_Block_Pattern_Categories_Registry.

**EXAMPLES**

    # List all registered pattern categories
    $ wp block pattern-category list

    # Get details about a specific category
    $ wp block pattern-category get featured --format=json





### wp block pattern-category list

Lists registered block pattern categories.

~~~
wp block pattern-category list [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--field=<field>]
		Prints the value of a single field for each category.

	[--fields=<fields>]
		Limit the output to specific category fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each category:

* name
* label
* description

**EXAMPLES**

    # List all pattern categories
    $ wp block pattern-category list

    # Get category names only
    $ wp block pattern-category list --field=name

    # Export categories to JSON
    $ wp block pattern-category list --format=json



### wp block pattern-category get

Gets details about a registered block pattern category.

~~~
wp block pattern-category get <name> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<name>
		Category name (e.g., 'buttons', 'columns').

	[--field=<field>]
		Instead of returning the whole category, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get details about the 'buttons' category
    $ wp block pattern-category get buttons

    # Get as JSON
    $ wp block pattern-category get featured --format=json



### wp block style

Retrieves details on registered block styles.

~~~
wp block style
~~~

Get information on block style variations from the WP_Block_Styles_Registry.

**EXAMPLES**

    # List all registered block styles
    $ wp block style list

    # List styles for a specific block
    $ wp block style list --block=core/button

    # Get details about a specific style
    $ wp block style get core/button outline





### wp block style list

Lists registered block styles.

~~~
wp block style list [--block=<block>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--block=<block>]
		Filter by block type name (e.g., 'core/button').

	[--field=<field>]
		Prints the value of a single field for each style.

	[--fields=<fields>]
		Limit the output to specific style fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each style:

* block_name
* name
* label
* is_default

These fields are optionally available:

* style_handle
* inline_style

**EXAMPLES**

    # List all block styles
    $ wp block style list

    # List styles for the button block
    $ wp block style list --block=core/button

    # List all styles as JSON
    $ wp block style list --format=json



### wp block style get

Gets details about a registered block style.

~~~
wp block style get <block> <style> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<block>
		Block type name (e.g., 'core/button').

	<style>
		Style name (e.g., 'outline').

	[--field=<field>]
		Instead of returning the whole style, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get the outline style for buttons
    $ wp block style get core/button outline

    # Get as JSON
    $ wp block style get core/button outline --format=json



### wp block binding

Retrieves details on registered block binding sources.

~~~
wp block binding
~~~

Get information on block binding sources from the WP_Block_Bindings_Registry.
Block bindings allow dynamic data to be connected to block attributes.

**EXAMPLES**

    # List all registered binding sources
    $ wp block binding list

    # Get details about the post-meta binding
    $ wp block binding get core/post-meta --format=json





### wp block binding list

Lists registered block binding sources.

~~~
wp block binding list [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--field=<field>]
		Prints the value of a single field for each source.

	[--fields=<fields>]
		Limit the output to specific source fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each source:

* name
* label

These fields are optionally available:

* uses_context

**EXAMPLES**

    # List all binding sources
    $ wp block binding list

    # Get source names only
    $ wp block binding list --field=name

    # Export sources to JSON
    $ wp block binding list --format=json



### wp block binding get

Gets details about a registered block binding source.

~~~
wp block binding get <name> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<name>
		Binding source name (e.g., 'core/post-meta').

	[--field=<field>]
		Instead of returning the whole source, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get details about the post-meta binding
    $ wp block binding get core/post-meta

    # Get as JSON
    $ wp block binding get core/post-meta --format=json



### wp block template

Retrieves details on block templates and template parts.

~~~
wp block template
~~~

Get information on block templates used in Full Site Editing (FSE) themes.

**EXAMPLES**

    # List all templates
    $ wp block template list

    # List template parts for the header area
    $ wp block template list --type=wp_template_part --area=header

    # Get a specific template
    $ wp block template get twentytwentyfour//single





### wp block template list

Lists block templates or template parts.

~~~
wp block template list [--type=<type>] [--slug=<slug>] [--area=<area>] [--post-type=<post-type>] [--source=<source>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--type=<type>]
		Template type.
		---
		default: wp_template
		options:
		  - wp_template
		  - wp_template_part
		---

	[--slug=<slug>]
		Filter by template slug(s). Accepts a single slug or comma-separated list.

	[--area=<area>]
		For template parts, filter by area (header, footer, sidebar, uncategorized).

	[--post-type=<post-type>]
		Filter templates by post type they apply to.

	[--source=<source>]
		Filter by source (theme, plugin, custom).

	[--field=<field>]
		Prints the value of a single field for each template.

	[--fields=<fields>]
		Limit the output to specific template fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each template:

* id
* slug
* title
* source
* type

These fields are optionally available:

* theme
* description
* status
* origin
* is_custom
* has_theme_file
* author
* area (template parts only)
* content

**EXAMPLES**

    # List all templates
    $ wp block template list

    # List template parts for the header area
    $ wp block template list --type=wp_template_part --area=header

    # List templates from the theme
    $ wp block template list --source=theme

    # List specific templates by slug
    $ wp block template list --slug=single,archive

    # List templates for a specific post type
    $ wp block template list --post-type=page

    # Export templates as JSON
    $ wp block template list --format=json



### wp block template get

Gets details about a block template.

~~~
wp block template get <id> [--type=<type>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		Template ID in format 'theme//slug' (e.g., 'twentytwentyfour//single').

	[--type=<type>]
		Template type.
		---
		default: wp_template
		options:
		  - wp_template
		  - wp_template_part
		---

	[--field=<field>]
		Instead of returning the whole template, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get the single post template
    $ wp block template get twentytwentyfour//single

    # Get template content only
    $ wp block template get twentytwentyfour//single --field=content

    # Get as JSON
    $ wp block template get twentytwentyfour//single --format=json



### wp block template export

Exports a block template to a file.

~~~
wp block template export <id> [--type=<type>] [--dir=<directory>] [--stdout]
~~~

**OPTIONS**

	<id>
		Template ID to export.

	[--type=<type>]
		Template type.
		---
		default: wp_template
		options:
		  - wp_template
		  - wp_template_part
		---

	[--dir=<directory>]
		Directory to export to. Defaults to current directory.

	[--stdout]
		Output to stdout instead of file.

**EXAMPLES**

    # Export template to file
    $ wp block template export twentytwentyfour//single

    # Export to stdout
    $ wp block template export twentytwentyfour//single --stdout

    # Export to specific directory
    $ wp block template export twentytwentyfour//single --dir=./templates/



### wp block synced-pattern

Manages synced patterns (reusable blocks).

~~~
wp block synced-pattern
~~~

Synced patterns are stored as the 'wp_block' post type and can be either
synced (changes reflect everywhere) or not synced (regular patterns).

**EXAMPLES**

    # List all synced patterns
    $ wp block synced-pattern list

    # Create a synced pattern
    $ wp block synced-pattern create --title="My Pattern" --content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'

    # Delete a synced pattern
    $ wp block synced-pattern delete 123





### wp block synced-pattern list

Lists synced patterns.

~~~
wp block synced-pattern list [--sync-status=<status>] [--search=<search>] [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	[--sync-status=<status>]
		Filter by sync status.
		---
		default: all
		options:
		  - synced
		  - unsynced
		  - all
		---

	[--search=<search>]
		Search by title.

	[--field=<field>]
		Prints the value of a single field for each pattern.

	[--fields=<fields>]
		Limit the output to specific fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - count
		  - yaml
		  - ids
		---

**AVAILABLE FIELDS**

These fields will be displayed by default for each pattern:

* ID
* post_title
* post_name
* sync_status
* post_date

These fields are optionally available:

* post_content
* post_status
* post_author

**EXAMPLES**

    # List all synced patterns
    $ wp block synced-pattern list --sync-status=synced

    # Search for patterns by title
    $ wp block synced-pattern list --search=hero

    # Export all synced patterns to JSON
    $ wp block synced-pattern list --format=json > synced-patterns.json



### wp block synced-pattern get

Gets details about a synced pattern.

~~~
wp block synced-pattern get <id> [--field=<field>] [--fields=<fields>] [--format=<format>]
~~~

**OPTIONS**

	<id>
		The synced pattern ID.

	[--field=<field>]
		Instead of returning the whole pattern, returns the value of a single field.

	[--fields=<fields>]
		Limit the output to specific fields. Defaults to all fields.

	[--format=<format>]
		Render output in a particular format.
		---
		default: table
		options:
		  - table
		  - csv
		  - json
		  - yaml
		---

**EXAMPLES**

    # Get a synced pattern
    $ wp block synced-pattern get 123

    # Get pattern content only
    $ wp block synced-pattern get 123 --field=post_content



### wp block synced-pattern create

Creates a synced pattern.

~~~
wp block synced-pattern create [--title=<title>] [--slug=<slug>] [--content=<content>] [--sync-status=<status>] [--status=<status>] [<file>] [--porcelain]
~~~

**OPTIONS**

	[--title=<title>]
		The pattern title.

	[--slug=<slug>]
		The pattern slug. Default: sanitized title.

	[--content=<content>]
		The block content.

	[--sync-status=<status>]
		Sync status.
		---
		default: synced
		options:
		  - synced
		  - unsynced
		---

	[--status=<status>]
		Post status.
		---
		default: publish
		---

	[<file>]
		Read content from file. Pass '-' for STDIN.

	[--porcelain]
		Output only the new pattern ID.

**EXAMPLES**

    # Create a synced pattern from content
    $ wp block synced-pattern create --title="My Hero" --content='<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->'

    # Create from file
    $ wp block synced-pattern create --title="Header" header.html

    # Create an unsynced pattern
    $ wp block synced-pattern create --title="Footer" --sync-status=unsynced footer.html

    # Create from STDIN
    $ cat content.html | wp block synced-pattern create --title="From STDIN" -



### wp block synced-pattern update

Updates a synced pattern.

~~~
wp block synced-pattern update <id> [--title=<title>] [--content=<content>] [--sync-status=<status>] [<file>]
~~~

**OPTIONS**

	<id>
		The synced pattern ID.

	[--title=<title>]
		The pattern title.

	[--content=<content>]
		The block content.

	[--sync-status=<status>]
		Sync status.
		---
		options:
		  - synced
		  - unsynced
		---

	[<file>]
		Read content from file. Pass '-' for STDIN.

**EXAMPLES**

    # Update pattern title
    $ wp block synced-pattern update 123 --title="Updated Hero"

    # Update content from file
    $ wp block synced-pattern update 123 updated-content.html

    # Change sync status
    $ wp block synced-pattern update 123 --sync-status=unsynced



### wp block synced-pattern delete

Deletes one or more synced patterns.

~~~
wp block synced-pattern delete <id>... [--force]
~~~

**OPTIONS**

	<id>...
		One or more synced pattern IDs.

	[--force]
		Skip trash and permanently delete.

**EXAMPLES**

    # Delete a synced pattern (to trash)
    $ wp block synced-pattern delete 123

    # Permanently delete
    $ wp block synced-pattern delete 123 --force

    # Delete multiple
    $ wp block synced-pattern delete 123 456 789

## Installing

This package is included with WP-CLI itself, no additional installation necessary.

To install the latest version of this package over what's included in WP-CLI, run:

    wp package install git@github.com:wp-cli/block-command.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/wp-cli/block-command/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/wp-cli/block-command/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/wp-cli/block-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

GitHub issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support


*This README.md is generated dynamically from the project's codebase using `wp scaffold package-readme` ([doc](https://github.com/wp-cli/scaffold-package-command#wp-scaffold-package-readme)). To suggest changes, please submit a pull request against the corresponding part of the codebase.*
