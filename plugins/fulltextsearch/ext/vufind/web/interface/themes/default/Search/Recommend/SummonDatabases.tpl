{if !empty($summonDatabases)}
<div class="authorbox">
  <p>{translate text='summon_database_recommendations'}</p>
  {foreach from=$summonDatabases item='current'}
    <p><a href="{$current.link|escape}">{$current.title|escape}</a><br/>{$current.description|escape}</p>
  {/foreach}
</div>
{/if}