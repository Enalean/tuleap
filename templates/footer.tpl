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
	  {* i18n: RSS = RSS *}
          <a href="{$SCRIPT_NAME}?p={$project}&a=rss" class="rss_logo">{$localize.RSS}</a>
	{/if}
      {else}
        {* i18n: OPML = OPML *}
        <a href="{$SCRIPT_NAME}?a=opml" class="rss_logo">{$localize.OPML}</a>
	{* i18n: TXT = TXT *}
        <a href="{$SCRIPT_NAME}?a=project_index" class="rss_logo">{$localize.TXT}</a>
      {/if}
    </div>
  </body>
</html>
