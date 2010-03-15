{*
 *  search.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Search view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

{include file='header.tpl'}

{* Nav *}
<div class="page_nav">
  <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$hash->GetHash()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$hash->GetHash()}">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$hash->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$hash->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree->GetHash()}&hb={$hash->GetHash()}">tree</a>
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash->GetHash()}&s={$search}&st={$searchtype}">first</a>
  {else}
    first
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash->GetHash()}&s={$search}&st={$searchtype}{if $page > 1}&pg={$page-1}{/if}" accesskey="p" title="Alt-p">prev</a>
  {else}
    prev
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash->GetHash()}&s={$search}&st={$searchtype}&pg={$page+1}" accesskey="n" title="Alt-n">next</a>
  {else}
    next
  {/if}
  <br />
</div>

{include file='title.tpl' titlecommit=$hash}

<table cellspacing="0">
  {* Print each match *}
  {foreach from=$results item=result}
    <tr class="{cycle values="light,dark"}">
      <td title="{if $result->GetAge() > 60*60*24*7*2}{$result->GetAge()|agestring}{else}{$result->GetCommitterEpoch()|date_format:"%F"}{/if}"><em>{if $result->GetAge() > 60*60*24*7*2}{$result->GetCommitterEpoch()|date_format:"%F"}{else}{$result->GetAge()|agestring}{/if}</em></td>
      <td>
        <em>
	  {if $searchtype == 'author'}
	    {$result->GetAuthorName()|highlight:$search}
	  {elseif $searchtype == 'committer'}
	    {$result->GetCommitterName()|highlight:$search}
	  {else}
	    {$result->GetAuthorName()}
	  {/if}
        </em>
      </td>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$result->GetHash()}" class="list" {if strlen($result->GetTitle()) > 50}title="{$result->GetTitle()}"{/if}><strong>{$result->GetTitle(50)}</strong>
      {if $searchtype == 'commit'}
        {foreach from=$result->SearchComment($search) item=line name=match}
          <br />{$line|highlight:$search}
        {/foreach}
      {/if}
      </td>
      {assign var=resulttree value=$result->GetTree()}
      <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$result->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$result->GetHash()}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$resulttree->GetHash()}&hb={$result->GetHash()}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=snapshot&h={$result->GetHash()}">snapshot</a>
      </td>
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=search&h={$hash->GetHash()}&s={$search}&st={$searchtype}&pg={$page+1}" title="Alt-n">next</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

