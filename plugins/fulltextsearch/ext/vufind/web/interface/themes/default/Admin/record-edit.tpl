<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
         <h1>{if $allowChanges}Edit{else}View{/if} Record</h1>

          {if $record}
          <form method="post">
            <table class="citation">
              {foreach from=$record item=value key=field}
                {if is_array($value)}
                  {foreach from=$value item=current}
                    <tr>
                    <th>{$field}: </th>
                    <td>
                      {if $allowChanges}
                        <input type="text" name="solr_{$field}[]" value="{$current|escape}" size="50">
                      {else}
                        <div style="width: 350px; overflow: auto;">{$current|escape}</div>
                      {/if}
                    </td>
                    </tr>
                  {/foreach}
                {else}
                  <tr>
                  <th>{$field}: </th>
                  <td>
                    {if $allowChanges}
                      <input type="text" name="solr_{$field}[]" value="{$value|escape}" size="50">
                    {else}
                      <div style="width: 350px; overflow: auto;">{$value|escape}</div>
                    {/if}
                  </td>
                  </tr>
                {/if}
              {/foreach}
            </table>
            {if $allowChanges}
            <input type="submit" name="submit" value="Save">
            {/if}
          </form>
          {else}
          <p>Could not load record {$recordId|escape}.</p>
          {/if}
        </div>
      </div>
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>