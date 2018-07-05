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
    <a href="{$SCRIPT_NAME}?a=commit&amp;h={$blamecommit->GetHash()|urlencode}" title="{$blamecommit->GetTitle()|htmlspecialchars}" class="commitTip"&noheader=1>{$blamecommit->GetAuthorEpoch()|date_format:"%Y-%m-%d %H:%M:%S"}</a>
    {$blamecommit->GetAuthorName()|escape}
  {/if}
  <br />
{/foreach}
{if $opened}</div>{/if}
