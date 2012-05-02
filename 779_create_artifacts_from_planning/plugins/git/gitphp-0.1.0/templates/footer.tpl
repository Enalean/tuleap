{*
 *  footer.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page footer template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
    <div class="page_footer">
      {if $project}
        {if $projectdescription}
          <div class="page_footer_text">{$projectdescription}</div>
        {/if}
	{if $validproject}
          <a href="{$SCRIPT_NAME}?p={$project}&a=rss&noheader=1" class="rss_logo">RSS</a>
	{/if}
      {else}
        <a href="{$SCRIPT_NAME}?a=opml" class="rss_logo">OPML</a>
        <a href="{$SCRIPT_NAME}?a=project_index" class="rss_logo">TXT</a>
      {/if}
    </div>
