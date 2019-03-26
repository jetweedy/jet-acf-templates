# jet-acf-templates
Template engine for custom post type and custom field output.

### Markup Sample
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

