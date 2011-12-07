{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='main.tpl'}

{block name=javascriptpaths}
{if file_exists('js/projectlist.min.js')}
GitPHPJSPaths.projectlist = "projectlist.min";
{/if}
{/block}
{block name=javascriptmodules}
GitPHPJSModules = ['projectlist'];
{/block}

{block name=main}

<div class="index_header">
{if file_exists('templates/hometext.tpl') }
{include file='hometext.tpl'}
{else}
{* default header *}
<p>
git source code archive
</p>
{/if}
</div>

<div class="projectSearch">
<form method="get" action="index.php" id="projectSearchForm" enctype="application/x-www-form-urlencoded">
{t}Search projects{/t}: <input type="text" name="s" class="projectSearchBox" {if $search}value="{$search}"{/if} /> <a href="index.php" class="clearSearch" {if !$search}style="display: none;"{/if}>X</a> {if $javascript}<img src="images/search-loader.gif" class="searchSpinner" style="display: none;" />{/if}
</form>
</div>

<table cellspacing="0" class="projectList">
  {foreach name=projects from=$projectlist item=proj}
    {if $smarty.foreach.projects.first}
      {* Header *}
      <tr class="projectHeader">
        {if $order == "project"}
          <th>{t}Project{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=project">{t}Project{/t}</a></th>
        {/if}
        {if $order == "descr"}
          <th>{t}Description{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=descr">{t}Description{/t}</a></th>
        {/if}
        {if $order == "owner"}
          <th>{t}Owner{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=owner">{t}Owner{/t}</a></th>
        {/if}
        {if $order == "age"}
          <th>{t}Last Change{/t}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=age">{t}Last Change{/t}</a></th>
        {/if}
        <th>{t}Actions{/t}</th>
      </tr>
    {/if}

    {if $currentcategory != $proj->GetCategory()}
      {assign var=currentcategory value=$proj->GetCategory()}
      {if $currentcategory != ''}
        <tr class="light categoryRow">
          <th class="categoryName">{$currentcategory}</th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      {/if}
    {/if}

    <tr class="{cycle values="light,dark"} projectRow">
      <td class="projectName">
        <a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=summary" class="list {if $currentcategory != ''}indent{/if}">{$proj->GetProject()}</a>
      </td>
      <td class="projectDescription"><a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=summary" class="list">{$proj->GetDescription()}</a></td>
      <td class="projectOwner"><em>{$proj->GetOwner()|escape:'html'}</em></td>
      {assign var=projecthead value=$proj->GetHeadCommit()}
      <td class="projectAge">
        {if $projecthead}
          {if $proj->GetAge() < 7200}   {* 60*60*2, or 2 hours *}
            <span class="agehighlight"><strong><em>{$proj->GetAge()|agestring}</em></strong></span>
          {elseif $proj->GetAge() < 172800}   {* 60*60*24*2, or 2 days *}
            <span class="agehighlight"><em>{$proj->GetAge()|agestring}</em></span>
          {else}
            <em>{$proj->GetAge()|agestring}</em>
          {/if}
	{else}
	  <em class="empty">{t}No commits{/t}</em>
	{/if}
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=summary">{t}summary{/t}</a>
	{if $projecthead}
	| 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=shortlog">{t}shortlog{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=log">{t}log{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=tree">{t}tree{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=snapshot&amp;h=HEAD" class="snapshotTip">{t}snapshot{/t}</a>
	{/if}
      </td>
    </tr>
  {foreachelse}
    {if $search}
    <div class="message">{t 1=$search}No matches found for "%1"{/t}</div>
    {else}
    <div class="message">{t}No projects found{/t}</div>
    {/if}
  {/foreach}

</table>

{/block}

{block name=footer}
  <a href="{$SCRIPT_NAME}?a=opml" class="rss_logo">{t}OPML{/t}</a>
  <a href="{$SCRIPT_NAME}?a=project_index" class="rss_logo">{t}TXT{/t}</a>
{/block}

