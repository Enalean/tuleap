{*
 *  header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
  <body>
    <div class="page_header">
      <a href="http://git-scm.com" title="git homepage">
        <img src="images/git-logo.png" width="72" height="27" alt="git" class="logo" />
      </a>
      {if $supportedlocales}
      <div class="lang_select">
        <form action="{$SCRIPT_NAME}" method="get" id="frmLangSelect">
         <div>
	{foreach from=$requestvars key=var item=val}
	{if $var != "l"}
	<input type="hidden" name="{$var}" value="{$val}" />
	{/if}
	{/foreach}
	<label for="selLang">{t}language:{/t}</label>
	<select name="l" id="selLang">
	  {foreach from=$supportedlocales key=locale item=language}
	    <option {if $locale == $currentlocale}selected="selected"{/if} value="{$locale}">{if $language}{$language} ({$locale}){else}{$locale}{/if}</option>
	  {/foreach}
	</select>
	<input type="submit" value="{t}set{/t}" id="btnLangSet" />
         </div>
	</form>
      </div>
      {/if}
      <!-- <a href="index.php">{if $homelink}{$homelink}{else}{t}projects{/t}{/if}</a> / -->
      &gt;
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
                <option {if $searchtype == 'commit'}selected="selected"{/if} value="commit">{t}commit{/t}</option>
                <option {if $searchtype == 'author'}selected="selected"{/if} value="author">{t}author{/t}</option>
                <option {if $searchtype == 'committer'}selected="selected"{/if} value="committer">{t}committer{/t}</option>
              </select> {t}search{/t}: <input type="text" name="s" {if $search}value="{$search|escape}"{/if} />
            </div>
          </form>
        {/if}
      {/if}
    </div>
