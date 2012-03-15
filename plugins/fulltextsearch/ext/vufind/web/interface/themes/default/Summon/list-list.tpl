<form name="addForm">
  {foreach from=$recordSet item=record name="recordLoop"}
  {if ($smarty.foreach.recordLoop.iteration % 2) == 0}
  <div class="result alt record{$smarty.foreach.recordLoop.iteration}">
  {else}
  <div class="result record{$smarty.foreach.recordLoop.iteration}">
  {/if}
  
    <div class="yui-ge">
      <div class="yui-u first">
        {assign var=thumb value="Thumbnail-s"}
        {if $record.$thumb}
        <img src="{$record.$thumb|escape}" class="alignleft" alt="Cover Image"/>
        {else}
        <img src="{$path}/bookcover.php" class="alignleft" alt="{translate text='No Cover Image'}"/>
        {/if}
        
        <div class="resultitem">
          <div id="resultItemLine1">
            <a href="{$url}/Summon/Record?id={$record.ID.0|escape:"url"}"
            class="title">{if !$record.Title.0}{translate text='Title not available'}{else}{$record.Title.0|truncate:180:"..."|highlight:$lookfor}{/if}</a>
          </div>

          <div id="resultItemLine2">
            {if $record.Author}
            {translate text='by'}
            {foreach from=$record.Author item=author name="loop"}
              <a href="{$url}/Summon/Search?type=Author&amp;lookfor={$author|escape:"url"}">{$author|highlight:$lookfor}</a>{if !$smarty.foreach.loop.last},{/if} 
            {/foreach}
            <br>
            {/if}

            {if $record.PublicationTitle}{translate text='Published in'} {$record.PublicationTitle.0|escape}{/if}
            {assign var=pdxml value="PublicationDate_xml"}
            {if $record.$pdxml}({if $record.$pdxml.0.month}{$record.$pdxml.0.month|escape}/{/if}{if $record.$pdxml.0.day}{$record.$pdxml.0.day|escape}/{/if}{if $record.$pdxml.0.year}{$record.$pdxml.0.year|escape}){/if}{elseif $record.PublicationDate}{$record.PublicationDate.0|escape}{/if}
          </div>

          <div id="resultItemLine3">
            {if $record.Snippet.0 != ""}
            <blockquote>
              <span class="quotestart">&#8220;</span>{$record.Snippet.0|escape}<span class="quoteend">&#8221;</span>
            </blockquote>
            {/if}
          </div>

          <div id="resultItemLine4">
            {if $record.URI && (!$openUrlBase || !$record.hasFullText)}
            <a href="{$record.URI.0|escape}" class="fulltext">{translate text='Get full text'}</a>
            {elseif $openUrlBase}
            {include file="Search/openurl.tpl" openUrl=$record.openUrl}
            {/if}
          </div>

          <span class="iconlabel {$record.ContentType.0|getSummonFormatClass|escape}">{translate text=$record.ContentType.0}</span>

        </div>
      </div>
    
      {* TODO: make "save record" feature work:
      <div class="yui-u">
        <div id="saveLink{$record.id.0}">
          <a href="{$url}/Record/Save?id={$record.id.0}"
             onClick="getLightbox('Record', 'Save', 'Summon', '{$record.id.0}', null); return false;" class="fav tool">{translate text='Add to favorites'}</a>
          <ul id="lists{$record.id.0}"></ul>
          <script language="JavaScript" type="text/javascript">
            getSaveStatuses('{$record.id.0}');
          </script>
        </div>
      </div>
       *}
    </div>

    <span class="Z3988" title="{$record.openUrl|escape}"></span>

  </div>

{/foreach}
</form>

{* TODO: implement save statuses for Summon
{if $user}
<script type="text/javascript">
  doGetSaveStatuses();
</script>
{/if}
 *}