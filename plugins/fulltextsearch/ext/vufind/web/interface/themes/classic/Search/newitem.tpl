<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="record">
    	
        <form method="GET" action="{$url}/Search/NewItem" name="searchForm" class="search">
          <h2>{translate text='Find New Items'}</h2>
          <br>
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

        {* not currently supported: <p align="center"><a href="{$url}/Search/NewItem/RSS">{translate text='New Item Feed'}</a></p> *}

      </div>
    </div>
  </div>
</div>