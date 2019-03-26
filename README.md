# jet-acf-templates

A template engine for custom post type and custom field output.

  This plugin hasn't been very well documented yet, but has been tested a great deal on a few sites including [chip.unc.edu](https:chip.unc.edu), [enable.unc.edu](https://enable.unc.edu), and [stationpubrun.com](https://stationpubrun.com). It basically allows you to use some markup within two \[jet-acf-template\] shorttags to display single or looped posts according to categories, post types, etc, as well as limit and paginate the results that come back up.

Generally speaking, it is a third piece of the "CPT-UI/ACF/..." trifecta for editing custom post types and displaying them in custom ways on your WordPress website without having to dig into your PHP code to code out custom display functionality.

## Installation

Install [Custom Post Type UI (CPT UI)](https://wordpress.org/plugins/custom-post-type-ui/) plugin.
Install the [Advanced Custom Fields](https://www.advancedcustomfields.com/) plugin.
Download the php file and drop it in your wp-content/plugins folder.

## Markup Sample
There's not a lot of documentation for this yet. However if you don't want to have to dig around the code to figure out how to use it, here's a head start: some markup that is used on one of the websites that this plugin has been implemented on:



```
[jet-acf-template post-type="post" category="frontpage" posts_per_page="2"]
{if: field:block_width op:eq args:"full":}
<div class="news-item width-full">{/if: field:block_width op:eq args:"full":}
{if: field:block_width op:eq args:"half":}
<div class="news-item width-half">{/if: field:block_width op:eq args:"half":}
<a class="title" href="{:post_permalink:}">{:post_title:}</a>
{if: field:image_width op:eq args:"full":}
<img class="full" src="{:post_image:}" />
{/if: field:image_width op:eq args:"full":}
{if: field:image_width op:eq args:"left":}
<img class="left" src="{:post_image:}" />
{/if: field:image_width op:eq args:"left":}
{if: field:image_width op:eq args:"right":}
<img class="right" src="{:post_image:}" />
{/if: field:image_width op:eq args:"right":}
{:post_blurb:}
<a href="{:post_permalink:}/"> Read more >> </a>
{if: field:block_width op:eq args:"full":}</div>
{/if: field:block_width op:eq args:"full":}
{if: field:block_width op:eq args:"half":}
</div>
{/if: field:block_width op:eq args:"half":}
[/jet-acf-template]
```

