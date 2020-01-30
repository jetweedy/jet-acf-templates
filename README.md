# jet-acf-templates

A template engine for custom post type and custom field output.

  This plugin hasn't been very well documented yet, but has been tested a great deal on a few sites including [chip.unc.edu](https:chip.unc.edu), [enable.unc.edu](https://enable.unc.edu), and [stationpubrun.com](https://stationpubrun.com). It basically allows you to use some markup within two \[jet-acf-template\] shorttags to display single or looped posts according to categories, post types, etc, as well as limit and paginate the results that come back up.

Generally speaking, it is a third piece of the "CPT-UI/ACF/..." trifecta for editing custom post types and displaying them in custom ways on your WordPress website without having to dig into your PHP code to code out custom display functionality.

## Installation and Use

1. Install [Custom Post Type UI (CPT UI)](https://wordpress.org/plugins/custom-post-type-ui/) plugin.
1. Install the [Advanced Custom Fields](https://www.advancedcustomfields.com/) plugin.
1. Download the php file and drop it in your wp-content/plugins folder.
1. Use markup (see sample below) in the content of pages that will display looped/paginated custom posts.

## Markup Sample
There's not a lot of documentation for this yet. However if you don't want to have to dig around the code to figure out how to use it, here's a head start: some markup that is used on one of the websites that this plugin has been implemented on:

```
[jet-acf-template post-type="post" category="frontpage" posts_per_page="2"]
  <div class="news-item width-full">
    <a class="title" href="{:post_permalink:}">{:post_title:}</a>
    {if: field:post_image op:neq args:"":}
     <img class="full" src="{:post_image:}" />
    {/if}
    {:post_blurb:}
    <ul>
      {rf:languages:}
        <li>{:language:}</li>
      {/rf}
    </ul>
    <a href="{:post_permalink:}/"> Read more >> </a>
    </div>
[/jet-acf-template]
```

## Planned

* Single value references in multi-value array values (like checkboxes) using "array[]" bracket notation
* Logical blocks for pseudo-coding PHP
* Markup for reading GET, POST and other variables
