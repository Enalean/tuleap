<script language="JavaScript" type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script language="JavaScript" type="text/javascript" src="{$path}/services/Summon/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
        <div class="record">
          {if $lastsearch}
            <a href="{$lastsearch|escape}" class="backtosearch">&laquo; {translate text="Back to Search Results"}</a>
          {/if}

          <ul class="tools">
            {* TODO: citations <li><a href="{$url}/Summon/Record/{$id}/Cite" class="cite" onClick="getLightbox('Summon', 'Cite', '{$id}', null, '{translate text="Cite this"}'); return false;">{translate text="Cite this"}</a></li> *}
            <li><a href="{$url}/Summon/SMS?id={$id|escape:"url"}" class="sms" onClick="getLightbox('Summon', 'SMS', '{$id|escape}', null, '{translate text="Text this"}'); return false;">{translate text="Text this"}</a></li>
            <li><a href="{$url}/Summon/Email?id={$id|escape:"url"}" class="mail" onClick="getLightbox('Summon', 'Email', '{$id|escape}', null, '{translate text="Email this"}'); return false;">{translate text="Email this"}</a></li>
            {* TODO: export <li><a href="{$url}/Summon/Record/{$id}/Export?style=endnote" class="export" onClick="showMenu('exportMenu'); return false;">{translate text="Import Record"}</a>
              <ul class="menu" id="exportMenu">
                <li><a href="{$url}/Summon/Record/{$id}/Export?style=refworks">{translate text="Import to"} RefWorks</a></li>
                <li><a href="{$url}/Summon/Record/{$id}/Export?style=endnote">{translate text="Import to"} EndNote</a></li>
              </ul>
            </li>
            *}
            {* TODO: save
            <li id="saveLink"><a href="{$url}/Record/{$id}/Save" class="fav" 
                                 onClick="getLightbox('Record', 'Save', '{$id}', null, '{translate text="Add to favorites"}'); return false;">{translate text="Add to favorites"}</a></li>
            <script language="JavaScript" type="text/javascript">
              getSaveStatus('{$id}', 'saveLink');
            </script>
             *}
          </ul>
          <div style="clear: both;"></div>

          {* Display link to content -- if a URL is provided, only use it if no 
             OpenURL setting exists or if the OpenURL won't lead to full text --
             these URI values aren't always very useful, so they should be linked
             as a last resort only. *}
          <div class="button alignright">
          {if $record.URI && (!$openUrlBase || !$record.hasFullText)}
            {foreach from=$record.URI.0 item="value"}
          <a href="{$value|escape}">{translate text='Get full text'}</a><br>
            {/foreach}
          {elseif $openUrlBase}
            {include file="Search/openurl.tpl" openUrl=$record.openUrl}
          {/if}
          </div>

          <div class="alignright"><span class="{$record.ContentType.0|replace:" ":""|escape}">{$record.ContentType.0|escape}</span></div>

          {* Display Title *}
          <h1>{$record.Title.0|escape}</h1>
          {* End Title *}

          {assign var=thumb value="Thumbnail-m"}
          {if $record.$thumb}
          {* Display Book Cover *}
          <div class="alignleft">
            <img alt="{translate text='Book Cover'}" src="{$record.$thumb|escape}">
          </div>
          {* End Book Cover *}
          {/if}
          
          {* Display Abstract/Snippet *}
          {if $record.Abstract}
            <p class="snippet">{$record.Abstract.0|escape}</p>
          {elseif $record.Snippet.0 != ""}
            <blockquote>
              <span class="quotestart">&#8220;</span>{$record.Snippet.0|escape}<span class="quoteend">&#8221;</span>
            </blockquote>
          {/if}

          {* Display Main Details *}
          <table cellpadding="2" cellspacing="0" border="0" class="citation">
          
            {if $record.Author}
            <tr valign="top">
              <th>{translate text='Author'}(s): </th>
              <td>
                {foreach from=$record.Author item="author" name="loop"}
                <a href="{$url}/Summon/Search?type=Author&amp;lookfor={$author|escape:"url"}">{$author|escape}</a>{if !$smarty.foreach.loop.last},{/if} 
                {/foreach}
              </td>
            </tr>
            {/if}

            {if $record.PublicationTitle}
            <tr valign="top">
              <th>{translate text='Publication'}: </th>
              <td>{$record.PublicationTitle.0|escape}</td>
            </tr>
            {/if}

            {assign var=pdxml value="PublicationDate_xml"}
            {if $record.$pdxml || $record.PublicationDate}
            <tr valign="top">
              <th>{translate text='Published'}: </th>
              <td>
              {if $record.$pdxml}
                {if $record.$pdxml.0.month}{$record.$pdxml.0.month|escape}/{/if}{if $record.$pdxml.0.day}{$record.$pdxml.0.day|escape}/{/if}{if $record.$pdxml.0.year}{$record.$pdxml.0.year|escape}{/if}
              {else}
                {$record.PublicationDate.0|escape}
              {/if}
              </td>
            </tr>
            {/if}

            {if $record.ISSN}
            <tr valign="top">
              <th>{translate text='ISSN'}: </th>
              <td>
              {foreach from=$record.ISSN item="value"}
                {$value|escape}<br>
              {/foreach}
              </td>
            </tr>
            {/if}
            
            {if $record.RelatedAuthor}
            <tr valign="top">
              <th>{translate text='Related Author'}: </th>
              <td>
                {foreach from=$record.RelatedAuthor item="author"}
                <a href="{$url}/Summon/Search?type=Author&amp;lookfor={$author|escape:"url"}">{$author|escape}</a>
                {/foreach}
              </td>
            </tr>
            {/if}

            {if $record.Volume}
            <tr valign="top">
              <th>{translate text='Volume'}: </th>
              <td>{$record.Volume.0|escape}</td>
            </tr>
            {/if}

            {if $record.Issue}
            <tr valign="top">
              <th>{translate text='Issue'}: </th>
              <td>{$record.Issue.0|escape}</td>
            </tr>
            {/if}

            {if $record.StartPage}
            <tr valign="top">
              <th>{translate text='Start Page'}: </th>
              <td>{$record.StartPage.0|escape}</td>
            </tr>
            {/if}

            {if $record.EndPage}
            <tr valign="top">
              <th>{translate text='End Page'}: </th>
              <td>{$record.EndPage.0|escape}</td>
            </tr>
            {/if}

            {if $record.Language}
            <tr valign="top">
              <th>{translate text='Language'}: </th>
              <td>{$record.Language.0|escape}</td>
            </tr>
            {/if}

            {if $record.SubjectTerms}
            <tr valign="top">
              <th>{translate text='Subjects'}: </th>
              <td>
                {foreach from=$record.SubjectTerms item=field name=loop}
                  <a href="{$path}/Summon/Search?type=SubjectTerms&amp;lookfor=%22{$field|escape:"url"}%22">{$field|escape}<br>
                {/foreach}
              </td>
            </tr>
            {/if}

            {* TODO: Fix Summon tag support:
            <tr valign="top">
              <th>{translate text='Tags'}: </th>
              <td>
                <span style="float:right;">
                  <a href="{$url}/Record/{$id}/AddTag" class="tool add"
                     onClick="getLightbox('Record', 'AddTag', '{$id}', null, '{translate text="Add Tag"}'); return false;">{translate text="Add"}</a>
                </span>
                <div id="tagList">
                  {if $tagList}
                    {foreach from=$tagList item=tag name=tagLoop}
                  <a href="{$url}/Search/Home?tag={$tag->tag}">{$tag->tag}</a> ({$tag->cnt}){if !$smarty.foreach.tagLoop.last}, {/if}
                    {/foreach}
                  {else}
                    {translate text='No Tags'}, {translate text='Be the first to tag this record'}!
                  {/if}
                </div>
              </td>
            </tr>
             *}
          </table>

          {* End Main Details *}
          
       </div>
       {* End Record *} 
        
       {* Add COINS *}  
       <span class="Z3988" title="{$record.openUrl|escape}"></span>    
      
    </div>
  </div>
 
</div>
