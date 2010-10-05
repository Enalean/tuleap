{*
 * blamedata.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame data column template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}

{foreach from=$blob->GetData(true) item=blobline name=blob}
  {assign var=blamecommit value=$blame[$smarty.foreach.blob.iteration]}
  {if $blamecommit}
    {if $opened}</div>{/if}
    <div class="{cycle values="light,dark"}">
    {assign var=opened value=true}
    <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$blamecommit->GetHash()}" title="{$blamecommit->GetTitle()}" class="commitTip">{$blamecommit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}</a>
    {$blamecommit->GetAuthorName()|escape}
  {/if}
  <br />
{/foreach}
{if $opened}</div>{/if}
