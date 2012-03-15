<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <b class="btop"><b></b></b>
      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>Delete Suppressed</h1>
          <table class="datagrid">
            <tr><th>Record ID</th><th>Status</th></tr>
            {foreach from=$resultList item=result}
            <tr>
              <td>{$result.id|escape}</td>
              <td>{if $result.status}:){else}X{/if}</td>
            </tr>
            {/foreach}
          </table>
        </div>
      </div>
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>