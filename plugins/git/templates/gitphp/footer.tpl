{*
 *  footer.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page footer template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
    <div class="page_footer">
      {if $project}
        <div class="page_footer_text">
	</div>
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=rss&amp;noheader=1" class="rss_logo">{t}RSS{/t}</a>
        <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=atom&amp;noheader=1" class="rss_logo">{t}Atom{/t}</a>
      {else}
        <a href="{$SCRIPT_NAME}?a=opml" class="rss_logo">{t}OPML{/t}</a>
        <a href="{$SCRIPT_NAME}?a=project_index" class="rss_logo">{t}TXT{/t}</a>
      {/if}
    </div>
  </body>
