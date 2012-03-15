<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>
    	
        <form method="GET" action="{$url}/Search/NewItem" name="searchForm" class="search">
          <div class="resulthead"><h3>{translate text='Find New Items'}</h3></div>
          <div class="page">

          <table>
            <tr><th>{translate text='Range'}: </th></tr>
            <tr>
              <td>
                {foreach from=$ranges item="range" key="key"}
                  <input type="radio" name="range" value="{$range|escape}"{if $key == 0} checked="checked"{/if}>
                  {if $range == 1}
                    {translate text='Yesterday'}
                  {else}
                    {translate text='Past'} {$range|escape} {translate text='Days'}
                  {/if}
                  <br>
                {/foreach}
                <br>
              </td>
            </tr>
            {if is_array($fundList) && !empty($fundList)}
            <tr><th>{translate text='Department'}: </th></tr>
            <tr>
              <td>
                <select name="department" size="10">
                {foreach from=$fundList item="fund" key="fundId"}
                  <option value="{$fundId|escape}">{$fund|escape}</option>
                {/foreach}
                </select>
              </td>
            </tr>
            {/if}
          </table>
          <input type="submit" name="submit" value="{translate text='Find'}">
        </form>
        <br>
        <hr>
        {* not currently supported: <p><a href="{$url}/Search/NewItem/RSS" class="feed">{translate text='New Item Feed'}</a></p> *}

      </div>
      
      <b class="bbot"><b></b></b>
    </div>
  </div>
</div>