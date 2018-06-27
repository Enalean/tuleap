{*
 * filediffsidebyside
 *
 * File diff with side-by-side changes template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @author Mattias Ulbrich
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
<table class="diffTable">
  {if $filediff->GetStatus() == 'D'}
    {assign var=delblob value=$filediff->GetFromBlob()}
    {foreach from=$delblob->GetData(true) item=blobline}
      <tr class="diff-deleted">
        <td class="diff-left">{$blobline|escape}</td>
	<td>&nbsp;</td>
      </tr>
    {/foreach}
  {elseif $filediff->GetStatus() == 'A'}
    {assign var=newblob value=$filediff->GetToBlob()}
    {foreach from=$newblob->GetData(true) item=blobline}
      <tr class="diff-added">
        <td class="diff-left">&nbsp;</td>
	<td>{$blobline|escape}</td>
      </tr>
    {/foreach}
  {else}
    {foreach from=$diffsplit item=lineinfo}
      {if $lineinfo[0]=='added'}
      <tr class="diff-added">
      {elseif $lineinfo[0]=='deleted'}
      <tr class="diff-deleted">
      {elseif $lineinfo[0]=='modified'}
      <tr class="diff-modified">
      {else}
      <tr>
      {/if}
        <td class="diff-left">{if $lineinfo[1]}{$lineinfo[1]|escape}{else}&nbsp;{/if}</td>
        <td>{if $lineinfo[2]}{$lineinfo[2]|escape}{else}&nbsp;{/if}</td>
      </tr>
    {/foreach}
  {/if}
</table>
