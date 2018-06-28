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
  {include file='nav.tpl' logcommit=$commit treecommit=$commit}
  <br />
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()|urlencode}&amp;s={$search|urlencode}&amp;st={$searchtype|urlencode}">{t}first{/t}</a>
  {else}
    {t}first{/t}
  {/if}
    &sdot; 
  {if $page > 0}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()|urlencode}&amp;s={$search|urlencode}&amp;st={$searchtype|urlencode}{if $page > 1}&amp;pg={$page-1}{/if}" accesskey="p" title="Alt-p">{t}prev{/t}</a>
  {else}
    {t}prev{/t}
  {/if}
    &sdot; 
  {if $hasmore}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()|urlencode}&amp;s={$search|urlencode}&amp;st={$searchtype|urlencode}&amp;pg={$page+1}" accesskey="n" title="Alt-n">{t}next{/t}</a>
  {else}
    {t}next{/t}
  {/if}
  <br />
</div>

{include file='title.tpl' titlecommit=$commit}

<table cellspacing="0">
  {* Print each match *}
  {foreach from=$results item=result}
    <tr class="{cycle values="light,dark"}">
      <td title="{if $result->GetAge() > 60*60*24*7*2}{$result->GetAge()|agestring|escape}{else}{$result->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{/if}"><em>{if $result->GetAge() > 60*60*24*7*2}{$result->GetCommitterEpoch()|date_format:"%Y-%m-%d"}{else}{$result->GetAge()|agestring|escape}{/if}</em></td>
      <td>
        <em>
	  {if $searchtype == 'author'}
	    {$result->GetAuthorName()|highlight:$search}
	  {elseif $searchtype == 'committer'}
	    {$result->GetCommitterName()|highlight:$search}
	  {else}
	    {$result->GetAuthorName()|escape}
	  {/if}
        </em>
      </td>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$result->GetHash()|urlencode}" class="list commitTip" {if strlen($result->GetTitle()) > 50}title="{$result->GetTitle()|htmlspecialchars}"{/if}><strong>{$result->GetTitle(50)|htmlspecialchars}</strong>
      {if $searchtype == 'commit'}
        {foreach from=$result->SearchComment($search) item=line name=match}
          <br />{$line|highlight:$search:50:htmlspecialchars}
        {/foreach}
      {/if}
      </td>
      {assign var=resulttree value=$result->GetTree()}
      <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$result->GetHash()}">{t}commit{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commitdiff&amp;h={$result->GetHash()}">{t}commitdiff{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;h={$resulttree->GetHash()|urlencode}&amp;hb={$result->GetHash()|urlencode}">{t}tree{/t}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=snapshot&amp;h={$result->GetHash()|urlencode}&amp;noheader=1" class="snapshotTip">{t}snapshot{/t}</a>
      </td>
    </tr>
  {/foreach}

  {if $hasmore}
    <tr>
      <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=search&amp;h={$commit->GetHash()|urlencode}&amp;s={$search|urlencode}&amp;st={$searchtype|urlencode}&amp;pg={$page+1}" title="Alt-n">{t}next{/t}</a></td>
    </tr>
  {/if}
</table>

{include file='footer.tpl'}

