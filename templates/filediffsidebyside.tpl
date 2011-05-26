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
</table>
