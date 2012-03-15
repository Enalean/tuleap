{if $user->cat_username}
  <ul class="pageitem">
  {if $transList}
  {foreach from=$transList item=resource name="recordLoop"}
    <li class="menu">
      {* TODO: implement resource icons in mobile template: <img src="images/{$resource.format|lower|regex_replace:"/[^a-z0-9]/":""}.png"> *}
      <a href="{$url}/Record/{$resource.id|escape:"url"}" class="title">{$resource.title|escape}</a>
    </li>
    <li class="textbox">
      <b>{translate text='Due'}: {$resource.duedate|escape}</b>
    </li>
  {/foreach}
  </ul>
  {else}
    <li class="textbox">{translate text='You do not have any items checked out'}.</li>
  {/if}
  </ul>
{else}
  {include file="MyResearch/catalog-login.tpl"}
{/if}

{include file="MyResearch/menu.tpl"}
