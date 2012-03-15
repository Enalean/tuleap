<form name="addForm">
{foreach from=$recordSet item=record name="recordLoop"}
  {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
  <div class="result alt record{$smarty.foreach.recordLoop.iteration}">
  {else}
  <div class="result record{$smarty.foreach.recordLoop.iteration}">
  {/if}
  
    <div class="yui-ge">
      <div class="yui-u first">
        {if $record.isbn}
        <img src="{$path}/bookcover.php?isn={$record.isbn|@formatISBN}&amp;size=small" class="alignleft" alt="{translate text='Cover Image'}">
        {else}
        <img src="{$path}/bookcover.php" class="alignleft" alt="{translate text='No Cover Image'}">
        {/if}
        <div class="resultitem">
          <div class="resultItemLine1">
          <a href="{$url}/WorldCat/Record?id={$record.id|escape}" class="title">{if !$record.title}{translate text='Title not available'}{else}{$record.title|truncate:180:"..."|highlight:$lookfor}{/if}</a>
          {if $record.title2}
          <br>
          {$record.title2|truncate:180:"..."|highlight:$lookfor}
          {/if}
          </div>

          <div class="resultItemLine2">
          {if $record.author}
          {translate text='by'}
          {if is_array($record.author)}
            {foreach from=$record.author item=author}
              <a href="{$url}/WorldCat/Search?lookfor={$author|escape:"url"}&amp;type=srw.au">{$author|highlight:$lookfor}</a>
            {/foreach}
          {else}
            <a href="{$url}/WorldCat/Search?lookfor={$record.author|escape:"url"}&amp;type=srw.au">{$record.author|highlight:$lookfor}</a>
          {/if}
          {/if}
    
          {if $record.publishDate}{translate text='Published'} {$record.publishDate|escape}{/if}
          </div>

          <div class="resultItemLine3">
          {if $record.callnumber}
          <b>{translate text='Call Number'}:</b> {$record.callnumber|escape}<br>
          {/if}
          {if $record.url}
            {if is_array($record.url)}
              {foreach from=$record.url item=recordurl}
                 <a href="{$recordurl}" class="fulltext" target="new">{translate text='Get full text'}</a><br>
              {/foreach}
            {else}
               <a href="{$record.url}" class="fulltext" target="new">{translate text='Get full text'}</a>
            {/if}
          {else}
            {if $record.issn && $openUrlBase}
              {if is_array($record.issn)}
                {assign var='currentIssn' value=$record.issn.0|escape:"url"}
              {else}
                {assign var='currentIssn' value=$record.issn|escape:"url"}
              {/if}
              {assign var='extraParams' value="issn=`$currentIssn`&genre=journal"}
              {include file="Search/openurl.tpl" openUrl=$extraParams}
            {/if}
          {/if}
          </div>
          {if $record.format}
            {if is_array($record.format)}
              {foreach from=$record.format item=format}
                <span class="iconlabel {$format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$format}</span>
              {/foreach}
            {else}
              <span class="iconlabel {$record.format|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$record.format}</span>
            {/if}
          {/if}
        </div>
      </div>
    
      {* TODO: make save record work
      <div class="yui-u">
        <div id="saveLink{$record.id}">
          <a href="{$url}/WorldCat/Save?id={$record.id}" onClick="getLightbox('WorldCat', 'Save', '{$record.id}', null); return false;" class="fav tool">{translate text='Add to favorites'}</a>
          <ul id="lists{$record.id}"></ul>
          <script language="JavaScript" type="text/javascript">
            getSaveStatuses('{$record.id}');
          </script>
        </div>
      </div>
       *}
    </div>

    <span class="Z3988"
      {if $record.isbn}
        title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&amp;rfr_id=info%3Asid%2F{$coinsID|escape:"url"}%3Agenerator&amp;rft.genre=book&amp;rft.btitle={$record.title|escape:"url"}&amp;rft.title={$record.title|escape:"url"}&amp;rft.series={$record.series|escape:"url"}&amp;rft.au={$record.author|escape:"url"}&amp;rft.date={$record.publishDate}&amp;rft.pub={$record.publisher|escape:"url"}&amp;rft.edition={$record.edition|escape:"url"}&amp;rft.isbn={$record.isbn|escape:"url"}">
      {* Disabled due to incompatibility with Zotero:
      {elseif $record.issn}
        title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;rfr_id=info%3Asid%2F{$coinsID|escape:"url"}%3Agenerator&amp;rft.genre=article&amp;rft.title={$record.title|escape:"url"}&amp;rft.date={$record.publishDate|escape:"url"}&amp;rft.issn={$record.issn|escape:"url"}">
       *}
      {else}
        title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc&amp;rfr_id=info%3Asid%2F{$coinsID|escape:"url"}%3Agenerator&amp;rft.title={$record.title|escape:"url"}&amp;rft.creator={$record.author|escape:"url"}&amp;rft.date={$record.publishDate|escape:"url"}&amp;rft.pub={$record.publisher|escape:"url"}&amp;rft.format={$record.format|escape:"url"}{if $record.issn}&amp;rft.issn={$record.issn|escape:"url"}{/if}">
      {/if}
    </span>

  </div>

{/foreach}
</form>
{* TODO: make save status work
{if $user}
<script type="text/javascript">
  doGetSaveStatuses();
</script>
{/if}
 *}