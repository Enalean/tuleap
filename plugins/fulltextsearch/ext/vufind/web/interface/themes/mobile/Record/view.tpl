<ul class="pageitem">
  <li>{include file=$coreMetadata}</li>
  <li>{include file="Record/$subTemplate"}</li>
</ul>

<ul class="pageitem">
  <li class="textbox"><span class="header">{translate text="Similar Items"}</span></li>
  {if is_array($similarRecords)}
    {foreach from=$similarRecords item=similar}
      <li class="menu"><a class="noeffect" href="{$url}/Record/{$similar.id|escape:"url"}"><span class="name">{$similar.title|escape}</span><span class="arrow"></span></a></li>
    {/foreach}
  {else}
    <li>{translate text='Cannot find similar records'}</li>
  {/if}
</ul>
{if is_array($editions)}
  <ul class="pageitem">
    <li class="textbox"><span class="header">{translate text="Other Editions"}</span></li>
    {foreach from=$editions item=edition}
       <li class="menu">
         <a class="noeffect" href="{$url}/Record/{$edition.id|escape:"url"}"><span class="name">{$edition.title|escape}</span><span class="arrow"></span></a>
       </li>
    {/foreach}
  </ul>
{/if}
