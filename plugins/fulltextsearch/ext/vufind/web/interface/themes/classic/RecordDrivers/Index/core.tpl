{* Display Title *}
<h1>{$coreShortTitle|escape}</h1>
{if $coreSubtitle}<h2>{$coreSubtitle|escape}</h2>{/if}
{if $coreTitleSection}<h3>{$coreTitleSection|escape}</h3>{/if}
{if $coreTitleStatement}<h3>{$coreTitleStatement|escape}</h3>{/if}
{* End Title *}

{* Display Book Cover *}
<div class="alignleft">
  {if $isbn}
  <a href="{$path}/bookcover.php?isn={$isbn|escape:"url"}&amp;size=large">
    <img alt="{translate text='Book Cover'}" src="{$path}/bookcover.php?isn={$isbn|escape:"url"}&amp;size=medium"></a>
  {else}
  <img src="{$path}/bookcover.php" alt="{translate text='No Cover Image'}">
  {/if}
</div>
{* End Book Cover *}

{* Display Main Details *}
<table cellpadding="2" cellspacing="0" border="0" class="citation" summary="{translate text='Bibliographic Details'}">
  {if !empty($coreNextTitles)}
  <tr valign="top">
    <th>{translate text='New Title'}: </th>
    <td>
      {foreach from=$coreNextTitles item=field name=loop}
        <a href="{$url}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Title">{$field|escape}</a><br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($corePrevTitles)}
  <tr valign="top">
    <th>{translate text='Previous Title'}: </th>
    <td>
      {foreach from=$corePrevTitles item=field name=loop}
        <a href="{$url}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Title">{$field|escape}</a><br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($coreMainAuthor)}
  <tr valign="top">
    <th>{translate text='Main Author'}: </th>
    <td><a href="{$url}/Author/Home?author={$coreMainAuthor|escape:"url"}">{$coreMainAuthor|escape}</a></td>
  </tr>
  {/if}

  {if !empty($coreCorporateAuthor)}
  <tr valign="top">
    <th>{translate text='Corporate Author'}: </th>
    <td><a href="{$url}/Author/Home?author={$coreCorporateAuthor|escape:"url"}">{$coreCorporateAuthor|escape}</a></td>
  </tr>
  {/if}

  {if !empty($coreContributors)}
  <tr valign="top">
    <th>{translate text='Other Authors'}: </th>
    <td>
      {foreach from=$coreContributors item=field name=loop}
        <a href="{$url}/Author/Home?author={$field|escape:"url"}">{$field|escape}</a>{if !$smarty.foreach.loop.last}, {/if}
      {/foreach}
    </td>
  </tr>
  {/if}

  <tr valign="top">
    <th>{translate text='Format'}: </th>
    <td>
     {if is_array($recordFormat)}
      {foreach from=$recordFormat item=displayFormat name=loop}
        <span class="iconlabel {$displayFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$displayFormat}</span>
      {/foreach}
    {else}
      <span class="iconlabel {$recordFormat|lower|regex_replace:"/[^a-z0-9]/":""}">{translate text=$recordFormat}</span>
    {/if}  
    </td>
  </tr>

  <tr valign="top">
    <th>{translate text='Language'}: </th>
    <td>{foreach from=$recordLanguage item=lang}{$lang|escape}<br>{/foreach}</td>
  </tr>

  {if !empty($corePublications)}
  <tr valign="top">
    <th>{translate text='Published'}: </th>
    <td>
      {foreach from=$corePublications item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($coreEdition)}
  <tr valign="top">
    <th>{translate text='Edition'}: </th>
    <td>
      {$coreEdition|escape}
    </td>
  </tr>
  {/if}

  {* Display series section if at least one series exists. *}
  {if !empty($coreSeries)}
  <tr valign="top">
    <th>{translate text='Series'}: </th>
    <td>
      {foreach from=$coreSeries item=field name=loop}
        {* Depending on the record driver, $field may either be an array with
           "name" and "number" keys or a flat string containing only the series
           name.  We should account for both cases to maximize compatibility. *}
        {if is_array($field)}
          {if !empty($field.name)}
            <a href="{$url}/Search/Results?lookfor=%22{$field.name|escape:"url"}%22&amp;type=Series">{$field.name|escape}</a>
            {if !empty($field.number)}
              {$field.number|escape}
            {/if}
            <br>
          {/if}
        {else}
          <a href="{$url}/Search/Results?lookfor=%22{$field|escape:"url"}%22&amp;type=Series">{$field|escape}</a><br>
        {/if}
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($coreSubjects)}
  <tr valign="top">
    <th>{translate text='Subjects'}: </th>
    <td>
      {foreach from=$coreSubjects item=field name=loop}
        {assign var=subject value=""}
        {foreach from=$field item=subfield name=subloop}
          {if !$smarty.foreach.subloop.first} &gt; {/if}
          {assign var=subject value="$subject $subfield"}
          <a href="{$url}/Search/Results?lookfor=%22{$subject|escape:"url"}%22&amp;type=Subject">{$subfield|escape}</a>
        {/foreach}
        <br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($coreURLs) || $coreOpenURL}
  <tr valign="top">
    <th>{translate text='Online Access'}: </th>
    <td>
      {foreach from=$coreURLs item=desc key=url name=loop}
        <a href="{if $proxy}{$proxy}/login?url={$url|escape:"url"}{else}{$url|escape}{/if}">{$desc|escape}</a><br/>
      {/foreach}
      {if $coreOpenURL}
        {include file="Search/openurl.tpl" openUrl=$coreOpenURL}<br/>
      {/if}
    </td>
  </tr>
  {/if}

  <tr valign="top">
    <th>{translate text='Tags'}: </th>
    <td>
      <span style="float:right;">
        <a href="{$url}/Record/{$id|escape:"url"}/AddTag" class="tool add"
           onClick="getLightbox('Record', 'AddTag', '{$id|escape}', null, '{translate text="Add Tag"}'); return false;">{translate text="Add"}</a>
      </span>
      <div id="tagList">
        {if $tagList}
          {foreach from=$tagList item=tag name=tagLoop}
        <a href="{$url}/Search/Results?tag={$tag->tag|escape:"url"}">{$tag->tag|escape:"html"}</a> ({$tag->cnt}){if !$smarty.foreach.tagLoop.last}, {/if}
          {/foreach}
        {else}
          {translate text='No Tags'}, {translate text='Be the first to tag this record'}!
        {/if}
      </div>
    </td>
  </tr>
</table>
{* End Main Details *}
