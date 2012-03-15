<div id="record{$summId|escape}" class="yui-ge">
  <div class="yui-u first">
    {if $summISBN}
    <img src="{$path}/bookcover.php?isn={$summISBN|escape:"url"}&amp;size=small" class="alignleft" alt="{translate text='Cover Image'}"/>
    {else}
    <img src="{$path}/bookcover.php" class="alignleft" alt="{translate text='No Cover Image'}"/>
    {/if}
    <div class="resultitem">
      <div class="resultItemLine1">
      <a href="{$url}/Record/{$summId|escape:"url"}" class="title">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|truncate:180:"..."|highlight:$lookfor}{/if}</a>
      </div>

      <div class="resultItemLine2">
      {if !empty($summAuthor)}
      {translate text='by'}
      <a href="{$url}/Author/Home?author={$summAuthor|escape:"url"}">{$summAuthor|highlight:$lookfor}</a>
      {/if}

      {if $summDate}{translate text='Published'} {$summDate.0|escape}{/if}
      </div>

      <div class="resultItemLine3">
      <b>{translate text='Call Number'}:</b> <span id="callnumber{$summId|escape}">{translate text='Loading'}</span><br>
      <b>{translate text='Located'}:</b> <span id="location{$summId|escape}">{translate text='Loading'}</span>

      {if $summOpenUrl || !empty($summURLs)}
        {if $summOpenUrl}
          <br>
          {include file="Search/openurl.tpl" openUrl=$summOpenUrl}
        {/if}
        {foreach from=$summURLs key=recordurl item=urldesc}
          <br><a href="{$recordurl|escape}" class="fulltext" target="new">{if $recordurl == $urldesc}{translate text='Get full text'}{else}{$urldesc|escape}{/if}</a>
        {/foreach}
      {else}
        <div class="status" id="status{$summId|escape}">
          <span class="unknown" style="font-size: 8pt;">{translate text='Loading'}...</span>
        </div>
      {/if}
      </div>
      {foreach from=$summFormats item=format}
        <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
      {/foreach}
    </div>
  </div>

  <div class="yui-u">
    <div id="saveLink{$summId|escape}">
      <a href="{$url}/Record/{$summId|escape:"url"}/Save" onClick="getLightbox('Record', 'Save', '{$summId|escape}', '', '{translate text='Add to favorites'}', 'Record', 'Save', '{$summId|escape}'); return false;" class="fav tool">{translate text='Add to favorites'}</a>
      <ul id="lists{$summId|escape}"></ul>
      <script language="JavaScript" type="text/javascript">
        getSaveStatuses('{$summId|escape:"javascript"}');
      </script>
    </div>
  </div>
</div>

{if $summCOinS}<span class="Z3988" title="{$summCOinS|escape}"></span>{/if}

<script type="text/javascript">
  getStatuses('{$summId|escape:"javascript"}');
</script>
