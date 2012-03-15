{if !empty($catalogResults)}
<div class="box submenu catalogMini">
  <h4>{translate text='Catalog Results'}</h4>
  
  <ul class="similar">
    {foreach from=$catalogResults item=record}
    <li>
      {if is_array($record.format)}
        <span class="{$record.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
      {else}
        <span class="{$record.format|lower|regex_replace:"/[^a-z0-9]/":""}">
      {/if}
      <a href="{$url}/Record/{$record.id|escape:"url"}">{$record.title|escape}</a>
      </span>
      <span style="font-size: .8em">
      {if $record.author}
      <br>{translate text='By'}: {$record.author|escape}
      {/if}
      {if $record.publishDate}
      <br>{translate text='Published'}: ({$record.publishDate.0|escape})
      {/if}
      </span>
    </li>
    {/foreach}
  </ul>
  <hr>
  <p><a href="{$catalogSearchUrl|escape}">{translate text='More catalog results'}...</a></p>
</div>
{/if}