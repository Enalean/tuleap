{if $user->cat_username}
  <ul class="pageitem">
  {if is_array($recordList)}
  {foreach from=$recordList item=record name="recordLoop"}
    <li class="menu">
      {* TODO: implement resource icons in mobile template: <img src="images/{$resource.format|lower|regex_replace:"/[^a-z0-9]/":""}.png"> *}
      <a href="{$url}/Record/{$record.id|escape:"url"}" class="title">{$record.title|escape}</a>
    </li>
    <li class="textbox">
      <b>{translate text='Created'}:</b> {$record.createdate|escape}<br>
      <b>{translate text='Expires'}:</b> {$record.expiredate|escape}
    </li>
  {/foreach}
  {else}
  <li class="textbox">{translate text='You do not have any holds or recalls placed'}.</li>
  {/if}
  </ul>
{else}
  {include file="MyResearch/catalog-login.tpl"}
{/if}

{include file="MyResearch/menu.tpl"}

</div>