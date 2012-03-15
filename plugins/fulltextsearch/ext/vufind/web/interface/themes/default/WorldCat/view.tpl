<script language="JavaScript" type="text/javascript" src="{$path}/js/ajax_common.js"></script>
<script language="JavaScript" type="text/javascript" src="{$path}/services/WorldCat/ajax.js"></script>

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first">
      <b class="btop"><b></b></b>
        <div class="toolbar">
        <ul>
            {* TODO: WorldCat citations <li><a href="{$url}/WorldCat/{$id}/Cite" class="cite" onClick="getLightbox('Record', 'Cite', '{$id}', null, '{translate text="Cite this"}'); return false;">{translate text="Cite this"}</a></li> *}
            <li><a href="{$url}/WorldCat/SMS?id={$id|escape:"url"}" class="sms" onClick="getLightbox('WorldCat', 'SMS', '{$id|escape}', null, '{translate text="Text this"}'); return false;">{translate text="Text this"}</a></li>
            <li><a href="{$url}/WorldCat/Email?id={$id|escape:"url"}" class="mail" onClick="getLightbox('WorldCat', 'Email', '{$id|escape}', null, '{translate text="Email this"}'); return false;">{translate text="Email this"}</a></li>
            {* TODO: WorldCat Export <li><a href="{$url}/WorldCat/{$id}/Export?style=endnote" class="export" onClick="toggleMenu('exportMenu'); return false;">{translate text="Import Record"}</a>
              <ul class="menu" id="exportMenu">
                <li><a href="{$url}/WorldCat/{$id}/Export?style=refworks">{translate text="Import to"} RefWorks</a></li>
                <li><a href="{$url}/WorldCat/{$id}/Export?style=endnote">{translate text="Import to"} EndNote</a></li>
              </ul>
            </li>
            *}
            {* TODO: WorldCat Save
            <li id="saveLink"><a href="{$url}/WorldCat/{$id}/Save" class="fav" onClick="getLightbox('Record', 'Save', '{$id}', null, '{translate text="Add to favorites"}'); return false;">{translate text="Add to favorites"}</a></li>
            <script language="JavaScript" type="text/javascript">
              getSaveStatus('{$id}', 'saveLink');
            </script>
             *}
          </ul>
        </div>

        <div class="record">

        {* Display Book Cover *}
          
            {if $isbn}
            <div class="alignright">
                <a href="{$path}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=large">
                    <img alt="{translate text='Book Cover'}" class="recordcover" src="{$path}/bookcover.php?isn={$isbn|@formatISBN}&amp;size=medium">
                </a>
            </div>
            {else}
            {* <img src="{$path}/bookcover.php" alt="{translate text='No Cover Image'}"> *}
            {/if}
          
          {* End Book Cover *}


          {* Display Title *}
          {assign var=marcField value=$marc->getField('245')}
          <h1>{$marcField|getvalue:'a'|escape} {$marcField|getvalue:'b'|escape} {* {$marcField|getvalue:'c'|escape} *}</h1>
          {* End Title *}

          {assign var=marcField value=$marc->getField('520')}
          {if $marcField|getvalue:'a'}<p>{$marcField|getvalue:'a'|truncate:300:"..."|escape}  <a href='{$url}/WorldCat/Description?id={$id|escape:"url"}'>{translate text='Full description'}</a></p>{/if}


          {* Display Main Details *}
          <table cellpadding="2" cellspacing="0" border="0" class="citation">
            {assign var=marcField value=$marc->getField('100')}
            {if $marcField}
            <tr valign="top">
              <th>{translate text='Main Author'}: </th>
              <td><a href="{$url}/WorldCat/Search?lookfor={$marcField|getvalue:'a'|escape:"url"}{if $marcField|getvalue:'b'}+{$marcField|getvalue:'b'|escape:"url"}{/if}{if $marcField|getvalue:'c'}+{$marcField|getvalue:'c'|escape:"url"}{/if}{if $marcField|getvalue:'d'}+{$marcField|getvalue:'d'|escape:"url"}{/if}&amp;type=srw.au">{$marcField|getvalue:'a'|escape}{if $marcField|getvalue:'b'} {$marcField|getvalue:'b'|escape}{/if}{if $marcField|getvalue:'c'} {$marcField|getvalue:'c'|escape}{/if}{if $marcField|getvalue:'d'} {$marcField|getvalue:'d'|escape}{/if}</a></td>
            </tr>
            {/if}

            {assign var=marcField value=$marc->getField('110')}
            {if $marcField}
            <tr valign="top">
              <th>{translate text='Corporate Author'}: </th>
              <td>{$marcField|getvalue:'a'|escape}</td>
            </tr>
            {/if}

            {assign var=marcField value=$marc->getFields('700')}
            {if $marcField}
            <tr valign="top">
              <th>{translate text='Contributors'}: </th>
              <td>
                {foreach from=$marcField item=field name=loop}
                  <a href="{$url}/WorldCat/Search?lookfor={$field|getvalue:'a'|escape:"url"}{if $field|getvalue:'b'}+{$field|getvalue:'b'|escape:"url"}{/if}{if $field|getvalue:'c'}+{$field|getvalue:'c'|escape:"url"}{/if}{if $field|getvalue:'d'}+{$field|getvalue:'d'|escape:"url"}{/if}&amp;type=srw.au">{$field|getvalue:'a'|escape}{if $field|getvalue:'b'} {$field|getvalue:'b'|escape}{/if}{if $field|getvalue:'c'} {$field|getvalue:'c'|escape}{/if}{if $field|getvalue:'d'} {$field|getvalue:'d'|escape}{/if}</a>{if !$smarty.foreach.loop.last}, {/if}
                {/foreach}
              </td>
            </tr>
            {/if}

            {assign var=marcField value=$marc->getFields('260')}
            {if $marcField}
            <tr valign="top">
              <th>{translate text='Published'}: </th>
              <td>
                {foreach from=$marcField item=field name=loop}
                  {$field|getvalue:'a'|escape} {$field|getvalue:'b'|escape} {$field|getvalue:'c'|escape}<br>
                {/foreach}
              </td>
            </tr>
            {/if}

            {assign var=marcField value=$marc->getFields('250')}
            {if $marcField}
            <tr valign="top">
              <th>{translate text='Edition'}: </th>
              <td>
                {foreach from=$marcField item=field name=loop}
                  {$field|getvalue:'a'|escape}<br>
                {/foreach}
              </td>
            </tr>
            {/if}

            {* Load the three possible series fields -- 440 is deprecated but
               still exists in many catalogs. *}
            {assign var=marcField440 value=$marc->getFields('440')}
            {assign var=marcField490 value=$marc->getFields('490')}
            {assign var=marcField830 value=$marc->getFields('830')}
            
            {* Check for 490's with indicator 1 == 0; these should be displayed
               since they will have no corresponding 830 field.  Other 490s would
               most likely be redundant and can be ignored. *}
            {assign var=visible490 value=0}
            {if $marcField490}
              {foreach from=$marcField490 item=field}
                {if $field->getIndicator(1) == 0}
                  {assign var=visible490 value=1}
                {/if}
              {/foreach}
            {/if}
            
            {* Display series section if at least one series exists. *}
            {if $marcField440 || $visible490 || $marcField830}
            <tr valign="top">
              <th>{translate text='Series'}: </th>
              <td>
                {if $marcField440}
                  {foreach from=$marcField440 item=field name=loop}
                    <a href="{$url}/WorldCat/Search?lookfor=%22{$field|getvalue:'a'|escape:"url"}%22&amp;type=srw.se">{$field|getvalue:'a'|escape:"html"}</a><br>
                  {/foreach}
                {/if}
                {if $visible490}
                  {foreach from=$marcField490 item=field name=loop}
                    {if $field->getIndicator(1) == 0}
                      <a href="{$url}/WorldCat/Search?lookfor=%22{$field|getvalue:'a'|escape:"url"}%22&amp;type=srw.se">{$field|getvalue:'a'|escape:"html"}</a><br>
                    {/if}
                  {/foreach}
                {/if}
                {if $marcField830}
                  {foreach from=$marcField830 item=field name=loop}
                    <a href="{$url}/WorldCat/Search?lookfor=%22{$field|getvalue:'a'|escape:"url"}%22&amp;type=srw.se">{$field|getvalue:'a'|escape:"html"}</a><br>
                  {/foreach}
                {/if}
              </td>
            </tr>
            {/if}

            {if $marc->getFields('600') || $marc->getFields('610') || $marc->getFields('630') ||
                $marc->getFields('650') || $marc->getFields('651') || $marc->getFields('655')}
            <tr valign="top">
              <th>{translate text='Subjects'}: </th>
              <td>
                {assign var=marcField value=$marc->getFields('600')}
                {if $marcField}
                  {foreach from=$marcField item=field name=loop}
                    {assign var=subject value=""}
                    {foreach from=$field->getSubfields() item=subfield name=subloop}
                      {if !$smarty.foreach.subloop.first} &gt; {/if}
                      {assign var=subfield value=$subfield->getData()}
                      {assign var=subject value="$subject $subfield"}
                      <a href="{$url}/WorldCat/Search?lookfor={$subject|escape:"url"}&amp;type=srw.su">{$subfield|escape}</a>
                    {/foreach}
                    <br>
                  {/foreach}
                {/if}

                {assign var=marcField value=$marc->getFields('610')}
                {if $marcField}
                  {foreach from=$marcField item=field name=loop}
                    {assign var=subject value=""}
                    {foreach from=$field->getSubfields() item=subfield name=subloop}
                      {if !$smarty.foreach.subloop.first} &gt; {/if}
                      {assign var=subfield value=$subfield->getData()}
                      {assign var=subject value="$subject $subfield"}
                      <a href="{$url}/WorldCat/Search?lookfor={$subject|escape:"url"}&amp;type=srw.su">{$subfield|escape}</a>
                    {/foreach}
                    <br>
                  {/foreach}
                {/if}

                {assign var=marcField value=$marc->getFields('630')}
                {if $marcField}
                  {foreach from=$marcField item=field name=loop}
                    {assign var=subject value=""}
                    {foreach from=$field->getSubfields() item=subfield name=subloop}
                      {if !$smarty.foreach.subloop.first} &gt; {/if}
                      {assign var=subfield value=$subfield->getData()}
                      {assign var=subject value="$subject $subfield"}
                      <a href="{$url}/WorldCat/Search?lookfor={$subject|escape:"url"}&amp;type=srw.su">{$subfield|escape}</a>
                    {/foreach}
                    <br>
                  {/foreach}
                {/if}

                {assign var=marcField value=$marc->getFields('650')}
                {if $marcField}
                  {foreach from=$marcField item=field name=loop}
                    {assign var=subject value=""}
                    {foreach from=$field->getSubfields() item=subfield name=subloop}
                      {if !$smarty.foreach.subloop.first} &gt; {/if}
                      {assign var=subfield value=$subfield->getData()}
                      {assign var=subject value="$subject $subfield"}
                      <a href="{$url}/WorldCat/Search?lookfor={$subject|escape:"url"}&amp;type=srw.su">{$subfield|escape}</a>
                    {/foreach}
                    <br>
                  {/foreach}
                {/if}

                {assign var=marcField value=$marc->getFields('651')}
                {if $marcField}
                  {foreach from=$marcField item=field name=loop}
                    {assign var=subject value=""}
                    {foreach from=$field->getSubfields() item=subfield name=subloop}
                      {if !$smarty.foreach.subloop.first} &gt; {/if}
                      {assign var=subfield value=$subfield->getData()}
                      {assign var=subject value="$subject $subfield"}
                      <a href="{$url}/WorldCat/Search?lookfor={$subject|escape:"url"}&amp;type=srw.su">{$subfield|escape}</a>
                    {/foreach}
                    <br>
                  {/foreach}
                {/if}

                {assign var=marcField value=$marc->getFields('655')}
                {if $marcField}
                  {foreach from=$marcField item=field name=loop}
                    {assign var=subject value=""}
                    {foreach from=$field->getSubfields() item=subfield name=subloop}
                      {if !$smarty.foreach.subloop.first} &gt; {/if}
                      {assign var=subfield value=$subfield->getData()}
                      {assign var=subject value="$subject $subfield"}
                      <a href="{$url}/WorldCat/Search?lookfor={$subject|escape:"url"}&amp;type=srw.su">{$subfield|escape}</a>
                    {/foreach}
                    <br>
                  {/foreach}
                {/if}
              </td>
            </tr>
            {/if}

            {assign var=marcField value=$marc->getFields('856')}
            {if $marcField}
            <tr valign="top">
              <th>{translate text='Online Access'}: </th>
              <td>
                {foreach from=$marcField item=field name=loop}
                  <a href="{$field|getvalue:'u'|escape}">{if $field|getvalue:'z'}{$field|getvalue:'z'|escape}{else}{$field|getvalue:'u'|escape}{/if}</a><br>
                {/foreach}
              </td>
            </tr>
            {/if}

            {* TODO: Fix WorldCat tag support:
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
                  <a href="{$url}/Search/Results?tag={$tag->tag}">{$tag->tag}</a> ({$tag->cnt}){if !$smarty.foreach.tagLoop.last}, {/if}
                    {/foreach}
                  {else}
                    No Tags, Be the first to tag this record!
                  {/if}
                </div>
              </td>
            </tr>
             *}
          </table>
          {* End Main Details *}
          
       </div>{* End Record *} 
        
        
      <div id="tabnav">
            <ul>
              <li{if $tab == 'Holdings'} class="active"{/if}>
                <a href="{$url}/WorldCat/Holdings?id={$id|escape:"url"}#tabnav" class="first"><span></span>{translate text='Holdings'}</a>
              </li>
              {if $marc->getField('520')}
              <li{if $tab == 'Description'} class="active"{/if}>
                <a href="{$url}/WorldCat/Description?id={$id|escape:"url"}#tabnav" class="first"><span></span>{translate text='Description'}</a>
              </li>
              {/if}
              {if $marc->getFields('505')}
              <li{if $tab == 'TOC'} class="active"{/if}>
                <a href="{$url}/WorldCat/TOC?id={$id|escape:"url"}#tabnav" class="first"><span></span>{translate text='Table of Contents'}</a>
              </li>
              {/if}
              {if $hasReviews}
              <li{if $tab == 'Reviews'} class="active"{/if}>
                <a href="{$url}/WorldCat/Reviews?id={$id|escape:"url"}#tabnav" class="first"><span></span>{translate text='Reviews'}</a>
              </li>
              {/if}
              {if $hasExcerpt}
              <li{if $tab == 'Excerpt'} class="active"{/if}>
                <a href="{$url}/WorldCat/Excerpt?id={$id|escape:"url"}#tabnav" class="first"><span></span>{translate text='Excerpt'}</a>
              </li>
              {/if}
            </ul><div style="clear:both;"></div>
        </div>
        
        <div class="recordsubcontent">
          {include file="WorldCat/$subTemplate"}
        </div>


      {* Add COINS *}  
      {assign var=titleField value=$marc->getField('245')}
      {assign var=authorField value=$marc->getField('100')}
      {assign var=publishField value=$marc->getField('260')}
      {assign var=editionField value=$marc->getField('250')}
      {assign var=isbnField value=$marc->getField('020')}
      {assign var=issnField value=$marc->getField('022')}
      <span class="Z3988"
        {if $isbnField && $isbnField|getvalue:'a'}
          title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Abook&amp;rfr_id=info%3Asid%2F{$coinsID|escape:"url"}%3Agenerator&amp;rft.genre=book&amp;rft.btitle={$titleField|getvalue:'a'|escape:"url"}+{$titleField|getvalue:'b'|escape:"url"}&amp;rft.title={$titleField|getvalue:'a'|escape:"url"}+{$titleField|getvalue:'b'|escape:"url"}&amp;rft.au={$authorField|getvalue:'a'|escape:"url"}&amp;rft.date={$publishField|getvalue:'c'|escape:"url"}&amp;rft.pub={$publishField|getvalue:'a'|escape:"url"}&amp;rft.edition={$editionField|getvalue:'a'|escape:"url"}&amp;rft.isbn={$isbnField|getvalue:'a'|escape:"url"}">
        {* Disabled due to incompatibility with Zotero:
        {elseif $issnField && $issnField|getvalue:'a'}
          title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Ajournal&amp;rfr_id=info%3Asid%2F{$coinsID|escape:"url"}%3Agenerator&amp;rft.genre=article&amp;rft.title={$titleField|getvalue:'a'|escape:"url"}+{$titleField|getvalue:'b'|escape:"url"}&amp;rft.date={$publishField|getvalue:'c'|escape:"url"}&amp;rft.issn={$issnField|getvalue:'a'|escape:"url"}">
         *}
        {else}
          title="ctx_ver=Z39.88-2004&amp;rft_val_fmt=info%3Aofi%2Ffmt%3Akev%3Amtx%3Adc&amp;rfr_id=info%3Asid%2F{$coinsID|escape:"url"}%3Agenerator&amp;rft.title={$titleField|getvalue:'a'|escape:"url"}+{$titleField|getvalue:'b'|escape:"url"}&amp;rft.creator={$authorField|getvalue:'a'|escape:"url"}&amp;rft.date={$publishField|getvalue:'c'|escape:"url"}&amp;rft.pub={$publishField|getvalue:'a'|escape:"url"}{if $issnField && $issnField|getvalue:'a'}&amp;rft.issn={$issnField|getvalue:'a'|escape:"url"}{/if}">
        {/if}
      </span>

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
          <a href="{$url}/WorldCat/Record?id={$similar.id|escape:"url"}">{$similar.title|escape}</a>
          <span style="font-size: 80%">
          {if $similar.author}<br>{translate text='By'}: {$similar.author|escape}{/if}
          {if $similar.publishDate}{translate text='Published'}: ({$similar.publishDate|escape}){/if}
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
          <a href="{$url}/WorldCat/Record?id={$edition.id|escape:"url"}">{$edition.title|escape}</a>
          {$edition.edition|escape}
          {if $edition.publishDate}({$edition.publishDate|escape}){/if}
        </li>
        {/foreach}
      </ul>
    </div>
    {/if}

  </div>
</div>
