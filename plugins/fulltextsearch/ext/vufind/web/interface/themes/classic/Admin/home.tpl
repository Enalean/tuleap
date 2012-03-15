<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">

      <div class="yui-gf resulthead">
        {include file="Admin/menu.tpl"}
        <div class="yui-u">
          <h1>VuFind Administration</h1>
          <br>

          <h2>Bibliographic Index</h2>
          <table class="citation">
            <tr>
              <th>Record Count: </th>
              <td>{$data.biblio.index.numDocs._content}</td>
            </tr>
            <tr>
              <th>Optimized: </th>
              <td>
                {if $data.biblio.index.optimized._content == "false"}
                <span class="error">{$data.biblio.index.optimized._content}</span>
                {else}
                <span>{$data.biblio.index.optimized._content}</span>
                {/if}
              </td>
            </tr>
            <tr>
              <th>Start Time: </th>
              <td>{$data.biblio.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
            </tr>
            <tr>
              <th>Last Modified: </th>
              <td>{$data.biblio.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
            </tr>
            <tr>
              <th>Uptime: </th>
              <td>{$data.biblio.uptime._content|printms}</td>
            </tr>
          </table>
          
          <h2>Authority Index</h2>
          <table class="citation">
            <tr>
              <th>Record Count: </th>
              <td>{$data.authority.index.numDocs._content}</td>
            </tr>
            <tr>
              <th>Optimized: </th>
              <td>
                {if $data.authority.index.optimized._content == "false"}
                <span class="error">{$data.authority.index.optimized._content}</span>
                {else}
                <span>{$data.authority.index.optimized._content}</span>
                {/if}
              </td>
            </tr>
            <tr>
              <th>Start Time: </th>
              <td>{$data.authority.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
            </tr>
            <tr>
              <th>Last Modified: </th>
              <td>{$data.authority.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
            </tr>
            <tr>
              <th>Uptime: </th>
              <td>{$data.authority.uptime._content|printms}</td>
            </tr>
          </table>

          <h2>Usage Statistics Index</h2>
          <table class="citation">
            <tr>
              <th>Record Count: </th>
              <td>{$data.stats.index.numDocs._content}</td>
            </tr>
            <tr>
              <th>Optimized: </th>
              <td>
                {if $data.stats.index.optimized._content == "false"}
                <span class="error">{$data.stats.index.optimized._content}</span>
                {else}
                <span>{$data.stats.index.optimized._content}</span>
                {/if}
              </td>
            </tr>
            <tr>
              <th>Start Time: </th>
              <td>{$data.stats.startTime._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
            </tr>
            <tr>
              <th>Last Modified: </th>
              <td>{$data.stats.index.lastModified._content|date_format:"%b %d, %Y %l:%M:%S%p"}</td>
            </tr>
            <tr>
              <th>Uptime: </th>
              <td>{$data.stats.uptime._content|printms}</td>
            </tr>
          </table>
        </div>
      </div>
      
    </div>
  </div>
</div>
