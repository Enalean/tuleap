{*
 *  projectlist.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project list template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

{include file='header.tpl'}

{include file='hometext.tpl'}

{if $message}
  {* Something is wrong; display an error message instead of trying to list *}
  {include file='message.tpl'}
{else}
  <table cellspacing="0">
    {* Header *}
    <tr>
      {if $order == "project"}
        <th>Project</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=project">Project</a></th>
      {/if}
      {if $order == "descr"}
        <th>Description</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=descr">Description</a></th>
      {/if}
      {if $order == "owner"}
        <th>Owner</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=owner">Owner</a></th>
      {/if}
      {if $order == "age"}
        <th>Last Change</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=age">Last Change</a></th>
      {/if}
      <th>Actions</th>
    </tr>

    {if $categorizedprojects}
      {* Show categorized; categorized project lists nested associatively in the project
         list by category key *}
      {foreach from=$categorizedprojects key=categ item=plist}
        {if $categ != "none"}
          <tr>
            <th>{$categ}</th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
          </tr>
	{/if}
        {section name=proj loop=$plist}
          <tr class="{cycle values="light,dark"}">
            <td>
              <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=summary" class="list {if $categ != "none"}indent{/if}">{$plist[proj].project}</a>
            </td>
            <td><a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=summary" class="list">{$plist[proj].descr}</a></td>
            <td><i>{$plist[proj].owner}</i></td>
            <td>
              {if $plist[proj].age < 7200}   {* 60*60*2, or 2 hours *}
                <span class="agehighlight"><b><i>{$plist[proj].age_string}</i></b></span>
	      {elseif $plist[proj].age < 172800}   {* 60*60*24*2, or 2 days *}
                <span class="agehighlight"><i>{$plist[proj].age_string}</i></span>
              {else}
                <i>{$plist[proj].age_string}</i>
              {/if}
            </td>
            <td class="link"><a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=tree">tree</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=snapshot&h=HEAD&noheader=1">snapshot</a></td>
          </tr>
        {/section}
      {/foreach}

    {else}
      
      {* Show flat uncategorized project array *}
      {section name=proj loop=$projects}
        <tr class="{cycle values="light,dark"}">
          <td>
            <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=summary" class="list">{$projects[proj].project}</a>
          </td>
          <td><a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=summary" class="list">{$projects[proj].descr}</a></td>
          <td><i>{$projects[proj].owner}</i></td>
          <td>
            {if $projects[proj].age < 7200}   {* 60*60*2, or 2 hours *}
              <span class="agehighlight"><b><i>{$projects[proj].age_string}</i></b></span>
	    {elseif $projects[proj].age < 172800}   {* 60*60*24*2, or 2 days *}
              <span class="agehighlight"><i>{$projects[proj].age_string}</i></span>
	    {else}
              <i>{$projects[proj].age_string}</i>
            {/if}
          </td>
          <td class="link"><a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=tree">tree</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=snapshot&h=HEAD&noheader=1">snapshot</a></td>
        </tr>
      {/section}

    {/if}

  </table>
{/if}

{include file='footer.tpl'}

