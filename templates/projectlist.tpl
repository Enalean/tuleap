{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

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

<table cellspacing="0">
  {foreach name=projects from=$projectlist item=proj}
    {if $smarty.foreach.projects.first}
      {* Header *}
      <tr>
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
        <tr class="light">
          <th>{$currentcategory}</th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      {/if}
    {/if}

    <tr class="{cycle values="light,dark"}">
      <td>
        <a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=summary" class="list {if $currentcategory != ''}indent{/if}">{$proj->GetProject()}</a>
      </td>
      <td><a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=summary" class="list">{$proj->GetDescription()}</a></td>
      <td><em>{$proj->GetOwner()}</em></td>
      <td>
        {assign var=projecthead value=$proj->GetHeadCommit()}
	{if $projecthead}
          {if $projecthead->GetAge() < 7200}   {* 60*60*2, or 2 hours *}
            <span class="agehighlight"><strong><em>{$projecthead->GetAge()|agestring}</em></strong></span>
          {elseif $projecthead->GetAge() < 172800}   {* 60*60*24*2, or 2 days *}
            <span class="agehighlight"><em>{$projecthead->GetAge()|agestring}</em></span>
          {else}
            <em>{$projecthead->GetAge()|agestring}</em>
          {/if}
	{/if}
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=summary">{t}summary{/t}</a>
	{if $projecthead}
	| 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=shortlog">{t}shortlog{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=log">{t}log{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=tree">{t}tree{/t}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&amp;a=snapshot&amp;h=HEAD">{t}snapshot{/t}</a>
	{/if}
      </td>
    </tr>
  {foreachelse}
    <div class="message">{t}No projects found{/t}</div>
  {/foreach}

</table>

{include file='footer.tpl'}

