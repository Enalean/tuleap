{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

{include file='header.tpl'}

{include file='hometext.tpl'}

<table cellspacing="0">
  {foreach name=projects from=$projectlist item=proj}
    {if $smarty.foreach.projects.first}
      {* Header *}
      <tr>
        {if $order == "project"}
          <th>{$resources->GetResource('Project', 'Header')}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=project">{$resources->GetResource('Project', 'Header')}</a></th>
        {/if}
        {if $order == "descr"}
          <th>{$resources->GetResource('Description', 'Header')}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=descr">{$resources->GetResource('Description', 'Header')}</a></th>
        {/if}
        {if $order == "owner"}
          <th>{$resources->GetResource('Owner', 'Header')}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=owner">{$resources->GetResource('Owner', 'Header')}</a></th>
        {/if}
        {if $order == "age"}
          <th>{$resources->GetResource('Last Change', 'Header')}</th>
        {else}
          <th><a class="header" href="{$SCRIPT_NAME}?o=age">{$resources->GetResource('Last Change', 'Header')}</a></th>
        {/if}
        <th>{$resources->GetResource('Actions', 'Header')}</th>
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
        <a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=summary" class="list {if $currentcategory != ''}indent{/if}">{$proj->GetProject()}</a>
      </td>
      <td><a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=summary" class="list">{$proj->GetDescription()}</a></td>
      <td><em>{$proj->GetOwner()}</em></td>
      <td>
        {assign var=projecthead value=$proj->GetHeadCommit()}
        {if $projecthead->GetAge() < 7200}   {* 60*60*2, or 2 hours *}
          <span class="agehighlight"><strong><em>{$projecthead->GetAge()|agestring}</em></strong></span>
        {elseif $projecthead->GetAge() < 172800}   {* 60*60*24*2, or 2 days *}
          <span class="agehighlight"><em>{$projecthead->GetAge()|agestring}</em></span>
        {else}
          <em>{$projecthead->GetAge()|agestring}</em>
        {/if}
      </td>
      <td class="link">
        <a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=summary">{$resources->GetResource('summary')}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('shortlog')}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=log">{$resources->GetResource('log')}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=tree">{$resources->GetResource('tree')}</a> | 
	<a href="{$SCRIPT_NAME}?p={$proj->GetProject()|urlencode}&a=snapshot&h=HEAD">{$resources->GetResource('snapshot')}</a>
      </td>
    </tr>
  {foreachelse}
    <div class="message">No projects found</div>
  {/foreach}

</table>

{include file='footer.tpl'}

