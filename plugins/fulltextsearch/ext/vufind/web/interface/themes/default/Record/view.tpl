<script language="JavaScript" type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script language="JavaScript" type="text/javascript" src="{$path}/services/Record/ajax.js"></script>
{if !empty($addThis)}
<script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub={$addThis|escape:"url"}"></script>
{/if}

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>
        <div class="toolbar">
        <ul>
            <li><a href="{$url}/Record/{$id|escape:"url"}/Cite" class="cite" onClick="getLightbox('Record', 'Cite', '{$id|escape}', null, '{translate text="Cite this"}'); return false;">{translate text="Cite this"}</a></li>
            <li><a href="{$url}/Record/{$id|escape:"url"}/SMS" class="sms" onClick="getLightbox('Record', 'SMS', '{$id|escape}', null, '{translate text="Text this"}'); return false;">{translate text="Text this"}</a></li>
            <li><a href="{$url}/Record/{$id|escape:"url"}/Email" class="mail" onClick="getLightbox('Record', 'Email', '{$id|escape}', null, '{translate text="Email this"}'); return false;">{translate text="Email this"}</a></li>
            {if is_array($exportFormats) && count($exportFormats) > 0}
              <li>
                <a href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormats.0|escape:"url"}" class="export" onClick="toggleMenu('exportMenu'); return false;">{translate text="Export Record"}</a><br />
                <ul class="menu" id="exportMenu">
                  {foreach from=$exportFormats item=exportFormat}
                    <li><a {if $exportFormat=="RefWorks"}target="{$exportFormat}Main" {/if}href="{$url}/Record/{$id|escape:"url"}/Export?style={$exportFormat|escape:"url"}">{translate text="Export to"} {$exportFormat|escape}</a></li>
                  {/foreach}
                </ul>
              </li>
            {/if}
            <li id="saveLink"><a href="{$url}/Record/{$id|escape:"url"}/Save" class="fav" onClick="getLightbox('Record', 'Save', '{$id|escape}', null, '{translate text="Add to favorites"}'); return false;">{translate text="Add to favorites"}</a></li>
            {if !empty($addThis)}
            <li id="addThis"><a class="addThis addthis_button"" href="https://www.addthis.com/bookmark.php?v=250&amp;pub={$addThis|escape:"url"}">{translate text='Bookmark'}</a></li>
            {/if}
          </ul>
        </div>
        <script language="JavaScript" type="text/javascript">
          function redrawSaveStatus() {literal}{{/literal}
              getSaveStatus('{$id|escape:"javascript"}', 'saveLink');
          {literal}}{/literal}
          {if $user}redrawSaveStatus();{/if}
        </script>

        <div class="record">

          {if $error}<p class="error">{$error}</p>{/if}

          {if $previousRecord || $nextRecord}
          <div class="resultscroller">
            {if $previousRecord}<a href="{$url}/Record/{$previousRecord}">&laquo; {translate text="Prev"}</a>{/if}
            #{$currentRecordPosition} {translate text='of'} {$resultTotal}
            {if $nextRecord}<a href="{$url}/Record/{$nextRecord}">{translate text="Next"} &raquo;</a>{/if}
          </div>
          {/if}

          {include file=$coreMetadata}
        
        </div>{* End Record *} 
        
      <div id="tabnav">
            <ul>
              <li{if $tab == 'Holdings'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/Holdings#tabnav" class="first"><span></span>{translate text='Holdings'}</a>
              </li>
              <li{if $tab == 'Description'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/Description#tabnav" class="first"><span></span>{translate text='Description'}</a>
              </li>
              {if $hasTOC}
              <li{if $tab == 'TOC'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/TOC#tabnav" class="first"><span></span>{translate text='Table of Contents'}</a>
              </li>
              {/if}
              <li{if $tab == 'UserComments'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/UserComments#tabnav" class="first"><span></span>{translate text='Comments'}</a>
              </li>
              {if $hasReviews}
              <li{if $tab == 'Reviews'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/Reviews#tabnav" class="first"><span></span>{translate text='Reviews'}</a>
              </li>
              {/if}
              {if $hasExcerpt}
              <li{if $tab == 'Excerpt'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/Excerpt#tabnav" class="first"><span></span>{translate text='Excerpt'}</a>
              </li>
              {/if}
              <li{if $tab == 'Details'} class="active"{/if}>
                <a href="{$url}/Record/{$id|escape:"url"}/Details#tabnav" class="first"><span></span>{translate text='Staff View'}</a>
              </li>
            </ul><div style="clear:both;"></div>
        </div>
           
                    
        <div class="recordsubcontent">
          {include file="Record/$subTemplate"}
        </div>

          {* Add COINS *}  
          <span class="Z3988" title="{$openURL|escape}"></span>

      <b class="bbot"><b></b></b>


    </div>
    </div>
 
      
  <div class="yui-b">
  
  
    <div class="sidegroup">
      <h4>{translate text="Similar Items"}</h4>
      {if is_array($similarRecords)}
      <ul class="similar">
        {foreach from=$similarRecords item=similar}
        <li>
          {if is_array($similar.format)}
            <span class="{$similar.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
          {else}
            <span class="{$similar.format|lower|regex_replace:"/[^a-z0-9]/":""}">
          {/if}
          <a href="{$url}/Record/{$similar.id|escape:"url"}">{$similar.title|escape}</a>
          </span>
          <span style="font-size: 80%">
          {if $similar.author}<br>{translate text='By'}: {$similar.author|escape}{/if}
          {if $similar.publishDate} {translate text='Published'}: ({$similar.publishDate.0|escape}){/if}
          </span>
        </li>
        {/foreach}
      </ul>
      {else}
      <p>{translate text='Cannot find similar records'}</p>
      {/if}
    </div>

    {if is_array($editions)}
    <div class="sidegroup">
      <h4>{translate text="Other Editions"}</h4>
      <ul class="similar">
        {foreach from=$editions item=edition}
        <li>
          {if is_array($edition.format)}
            <span class="{$edition.format[0]|lower|regex_replace:"/[^a-z0-9]/":""}">
          {else}
            <span class="{$edition.format|lower|regex_replace:"/[^a-z0-9]/":""}">
          {/if}
          <a href="{$url}/Record/{$edition.id|escape:"url"}">{$edition.title|escape}</a>
          </span>
          {$edition.edition|escape}
          {if $edition.publishDate}({$edition.publishDate.0|escape}){/if}
        </li>
        {/foreach}
      </ul>
    </div>
    {/if}

  </div>
</div>
