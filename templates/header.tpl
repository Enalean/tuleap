{*
 *  header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
  <!-- gitphp web interface {$version}, (C) 2006 Christopher Han <xiphux@gmail.com> -->
  <head>
    <title>{$pagetitle}{if $project && $validproject} :: {$project}{if $action && $validaction}/{$localize.$action}{/if}{/if}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    {if $validproject}
      <link rel="alternate" title="{$project} log" href="{$SCRIPT_NAME}?p={$project}&a=rss" type="application/rss+xml" />
    {/if}
    <link rel="stylesheet" href="{$stylesheet}" type="text/css" />
    {$smarty.capture.header}
  </head>
  <body>
    <div class="page_header">
      <a href="http://www.kernel.org/pub/software/scm/git/docs/" title="git documentation">
        <img src="git-logo.png" width="72" height="27" alt="git" class="logo" />
      </a>
      {* i18n: projects = projects *}
      <a href="index.php">{$localize.projects}</a> / 
      {if $project && $validproject}
        <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$project}</a>
        {if $action && $validaction}
           / {$localize.$action}
        {/if}
        {if $enablesearch}
          <form method="get" action="index.php" enctype="application/x-www-form-urlencoded">
            <div class="search">
              <input type="hidden" name="p" value="{$project}" />
              <input type="hidden" name="a" value="search" />
              <input type ="hidden" name="h" value="{if $currentsearchhash}{$currentsearchhash}{else}HEAD{/if}" />
              <select name="st">
	        {* i18n: commit = commit *}
                <option {if $currentsearchtype == 'commit'}selected="selected"{/if} value="commit">{$localize.commit}</option>
                {* i18n: author = author *}
                <option {if $currentsearchtype == 'author'}selected="selected"{/if} value="author">{$localize.author}</option>
                {* i18n: committer = committer *}
                <option {if $currentsearchtype == 'committer'}selected="selected"{/if} value="committer">{$localize.committer}</option>
                {if $filesearch}
		  {* i18n: file = file *}
                  <option {if $currentsearchtype == 'file'}selected="selected"{/if} value="file">{$localize.file}</option>
                {/if}
		{* i18n: search = search *}
              </select> {$localize.search}: <input type="text" name="s" {if $currentsearch}value="{$currentsearch}"{/if} />
            </div>
          </form>
        {/if}
      {/if}
    </div>
