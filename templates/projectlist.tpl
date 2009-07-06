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
      {* i18n: headerproject = Project *}
      {if $order == "project"}
        <th>{$localize.headerproject}</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=project">{$localize.headerproject}</a></th>
      {/if}
      {* i18n: headerdescription = Description *}
      {if $order == "descr"}
        <th>{$localize.headerdescription}</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=descr">{$localize.headerdescription}</a></th>
      {/if}
      {* i18n: headerowner = Owner *}
      {if $order == "owner"}
        <th>{$localize.headerowner}</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=owner">{$localize.headerowner}</a></th>
      {/if}
      {* i18n: headerlastchange = Last Change *}
      {if $order == "age"}
        <th>{$localize.headerlastchange}</th>
      {else}
        <th><a class="header" href="{$SCRIPT_NAME}?o=age">{$localize.headerlastchange}</a></th>
      {/if}
      {* i18n: headeractions = Actions *}
      <th>{$localize.headeractions}</th>
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
            <td>{$plist[proj].descr}</td>
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
	    {* i18n: summary = summary *}
	    {* i18n: shortlog = shortlog *}
	    {* i18n: log = log *}
	    {* i18n: snapshot = snapshot *}
	    {* i18n: tree = tree *}
            <td class="link"><a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=tree">{$localize.tree}</a> | <a href="{$SCRIPT_NAME}?p={$plist[proj].project}&a=snapshot&h=HEAD">{$localize.snapshot}</a></td>
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
          <td>{$projects[proj].descr}</td>
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
	  {* i18n: summary = summary *}
	  {* i18n: shortlog = shortlog *}
	  {* i18n: log = log *}
	  {* i18n: snapshot = snapshot *}
	  {* i18n: tree = tree *}
          <td class="link"><a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=shortlog">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=log">{$localize.log}</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=tree">{$localize.tree}</a> | <a href="{$SCRIPT_NAME}?p={$projects[proj].project}&a=snapshot&h=HEAD">{$localize.snapshot}</a></td>
        </tr>
      {/section}

    {/if}

  </table>
{/if}

{include file='footer.tpl'}

