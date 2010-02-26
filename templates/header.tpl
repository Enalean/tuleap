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
    <title>{$pagetitle}{if $project && $validproject} :: {$project}{if $action && $validaction}/{$action}{/if}{/if}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    {if $validproject}
      <link rel="alternate" title="{$project} log" href="{$SCRIPT_NAME}?p={$project}&a=rss" type="application/rss+xml" />
    {/if}
    <link rel="stylesheet" href="css/{$stylesheet}" type="text/css" />
    <style type="text/css">
    <!--
    {$extracss}
    -->
    </style></head>
    {$smarty.capture.header}
  </head>
  <body>
    <div class="page_header">
      <a href="http://www.kernel.org/pub/software/scm/git/docs/" title="git documentation">
        <img src="images/git-logo.png" width="72" height="27" alt="git" class="logo" />
      </a>
      <a href="index.php">projects</a> / 
      {if $project && $validproject}
        <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$project}</a>
        {if $action && $validaction}
           / {$action}
        {/if}
        {if $enablesearch}
          <form method="get" action="index.php" enctype="application/x-www-form-urlencoded">
            <div class="search">
              <input type="hidden" name="p" value="{$project}" />
              <input type="hidden" name="a" value="search" />
              <input type ="hidden" name="h" value="{if $hashbase}{$hashbase}{elseif $hash}{$hash}{else}HEAD{/if}" />
              <select name="st">
                <option {if $searchtype == 'commit'}selected="selected"{/if} value="commit">commit</option>
                <option {if $searchtype == 'author'}selected="selected"{/if} value="author">author</option>
                <option {if $searchtype == 'committer'}selected="selected"{/if} value="committer">committer</option>
                {if $filesearch}
                  <option {if $searchtype == 'file'}selected="selected"{/if} value="file">file</option>
                {/if}
              </select> search: <input type="text" name="s" {if $search}value="{$search}"{/if} />
            </div>
          </form>
        {/if}
      {/if}
    </div>
