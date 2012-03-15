<div class="searchHome">
  <b class="btop"><b></b></b>       
  <div class="searchHomeContent"> 
    <img src="{$path}/interface/themes/default/images/vufind_logo_large.gif" alt="VuFind">  
  
    <div class="searchHomeForm">
      {include file="Summon/searchbox.tpl"}
    </div>

  </div>
   
</div>

<div class="searchHomeBrowseHeader">
  <div><h2>Browse by Format</h2></div>
  <div><h2>Browse by Language</h2></div>
  <br clear="all">
</div>

<div class="searchHomeBrowse">
  <div class="searchHomeBrowseInner">
    <div>
      <ul>
          {foreach from=$formatList.counts item=format}
          <li><a href="{$path}/Summon/Search?type=all&amp;filter[]={$formatList.displayName|escape:"url"}:{$format.value|escape:"url"}">{$format.value|escape}</a></li>
          {/foreach}
      </ul>
    </div>
    <div>
      <ul>
          {foreach from=$languageList.counts item=language}
          <li><a href="{$path}/Summon/Search?type=all&amp;filter[]={$languageList.displayName|escape:"url"}:{$language.value|escape:"url"}">{$language.value|escape}</a></li>
          {/foreach}
      </ul>
    </div>
  </div>
  <br clear="all">
  <b class="gbot"><b></b></b>
</div>
