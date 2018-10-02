{*
 *  header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
  <body>
    <div class="page_header">
      {if $project}
        <a href="{$SCRIPT_NAME}?a=tree">{$project->GetProject()|escape}</a>
        {if $actionlocal}
           / {$actionlocal}
        {/if}
        {if $enablesearch}
          <form method="get" action="{$SCRIPT_NAME}" enctype="application/x-www-form-urlencoded">
            <div class="search">
              <input type="hidden" name="a" value="search" />
              <input type ="hidden" name="h" value="{if $commit}{$commit->GetHash()|escape}{else}HEAD{/if}" />
              <select name="st">
                <option {if $searchtype == 'commit'}selected="selected"{/if} value="commit">{t domain="gitphp"}commit{/t}</option>
                <option {if $searchtype == 'author'}selected="selected"{/if} value="author">{t domain="gitphp"}author{/t}</option>
                <option {if $searchtype == 'committer'}selected="selected"{/if} value="committer">{t domain="gitphp"}committer{/t}</option>
              </select> {t domain="gitphp"}search{/t}: <input type="text" name="s" {if $search}value="{$search|escape}"{/if} />
            </div>
          </form>
        {/if}
      {/if}
    </div>
