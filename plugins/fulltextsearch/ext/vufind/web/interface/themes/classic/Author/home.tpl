<script language="JavaScript" type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script language="JavaScript" type="text/javascript" src="{$path}/services/Search/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
    
      <div class="record">
        {if $lastsearch}
          <p>  <a href="{$lastsearch|escape}" class="backtosearch">&laquo; {translate text="Back to Search Results"}</a></p>
        {/if}

        {if $info}
        <h2>{$info.name|escape}</h2><br>
  
        {if $info.image}
        <img src="{$info.image}" alt="{$info.altimage|escape}" width="150px" class="alignleft">
        {/if}
        {$info.description|truncate_html:4500:"...":false}
        <p>
          <br clear="All"><a href="http://{$wiki_lang}.wikipedia.org/wiki/{$info.name|escape:"url"}" target="new"><span class="note">{translate text='wiki_link'}</span></a>
        </p>
        {/if}
  
        <div class="resulthead">
          {translate text="Showing"}
          <b>{$recordStart}</b> - <b>{$recordEnd}</b>
          {translate text='of'} <b>{$recordCount}</b>
          {translate text='for search'}: <b>'{$authorName|escape:"html"}'</b>,
          {translate text='query time'}: {$qtime}s
        </div>

        {include file="Search/list-list.tpl"}

        {if $pageLinks.all}<div class="pagination">{$pageLinks.all}</div>{/if}
  
      </div>
      <div class="searchtools">
        <strong>{translate text='Search Tools'}:</strong>
        <a href="{$rssLink|escape}" class="feed">{translate text='Get RSS Feed'}</a>
        <a href="{$url}/Search/Email" class="mail" onClick="getLightbox('Search', 'Email', null, null, '{translate text="Email this"}'); return false;">{translate text='Email this Search'}</a>
      </div>
    </div>
  </div>
  
  {* Recommendations *}
  <div class="yui-b">
    {if $sideRecommendations}
      {foreach from=$sideRecommendations item="recommendations"}
        {include file=$recommendations}
      {/foreach}
    {/if}
  </div>
  {* End Recommendations *}

</div>
