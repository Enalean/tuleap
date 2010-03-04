{*
 *  footer.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page footer template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
    <div class="page_footer">
      {if $project}
        <div class="page_footer_text">{$project->GetDescription()}</div>
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=rss" class="rss_logo">RSS</a>
      {else}
        <a href="{$SCRIPT_NAME}?a=opml" class="rss_logo">OPML</a>
        <a href="{$SCRIPT_NAME}?a=project_index" class="rss_logo">TXT</a>
      {/if}
    </div>
  </body>
</html>
