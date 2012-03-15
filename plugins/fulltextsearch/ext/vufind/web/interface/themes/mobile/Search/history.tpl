<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="record">
      {if !$noHistory}
      {if $saved}
        <div class="resulthead"><h3>{translate text="history_saved_searches"}</h3></div>
        <table class="datagrid" width="100%">
          <tr>
            <th width="25%">{translate text="history_time"}</th>
            <th width="30%">{translate text="history_search"}</th>
            <th width="30%">{translate text="history_limits"}</th>
            <th width="10%">{translate text="history_results"}</th>
            <th width="5%">{translate text="history_delete"}</th>
          </tr>
          {foreach item=info from=$saved name=historyLoop}
          {if ($smarty.foreach.historyLoop.iteration % 2) == 0}
          <tr class="evenrow">
          {else}
          <tr class="oddrow">
          {/if}
            <td>{$info.time}</td>
            <td><a href="{$info.url|escape}">{if empty($info.description)}{translate text="history_empty_search"}{else}{$info.description|escape}{/if}</a></td>
            <td>{foreach from=$info.filters item=filters key=field}{foreach from=$filters item=filter}
              <b>{translate text=$field|escape}</b>: {$filter.value|escape}<br/>
            {/foreach}{/foreach}</td>
            <td>{$info.hits}</td>
            <td><a href="{$path}/MyResearch/SaveSearch?delete={$info.searchId|escape:"url"}&amp;mode=history" class="delete">{translate text="history_delete_link"}</a></td>
          </tr>
          {/foreach}
        </table>
        <br/>
      {/if}

      {if $links}
        <div class="resulthead"><h3>{translate text="history_recent_searches"}</h3></div>
        <table class="datagrid" width="100%">
          <tr>
            <th width="25%">{translate text="history_time"}</th>
            <th width="30%">{translate text="history_search"}</th>
            <th width="30%">{translate text="history_limits"}</th>
            <th width="10%">{translate text="history_results"}</th>
            <th width="5%">{translate text="history_save"}</th>
          </tr>
          {foreach item=info from=$links name=historyLoop}
          {if ($smarty.foreach.historyLoop.iteration % 2) == 0}
          <tr class="evenrow">
          {else}
          <tr class="oddrow">
          {/if}
            <td>{$info.time}</td>
            <td><a href="{$info.url|escape}">{if empty($info.description)}{translate text="history_empty_search"}{else}{$info.description|escape}{/if}</a></td>
            <td>{foreach from=$info.filters item=filters key=field}{foreach from=$filters item=filter}
              <b>{translate text=$field|escape}</b>: {$filter.value|escape}<br/>
            {/foreach}{/foreach}</td>
            <td>{$info.hits}</td>
            <td><a href="{$path}/MyResearch/SaveSearch?save={$info.searchId|escape:"url"}&amp;mode=history" class="add">{translate text="history_save_link"}</a></td>
          </tr>
          {/foreach}
        </table>
        <br/><a href="{$path}/Search/History?purge=true" class="delete">{translate text="history_purge"}</a>
      {/if}

      {else}
        <div class="resulthead"><h3>{translate text="history_recent_searches"}</h3></div>
        {translate text="history_no_searches"}
      {/if}
      </div>
    </div>
  </div>
</div>
