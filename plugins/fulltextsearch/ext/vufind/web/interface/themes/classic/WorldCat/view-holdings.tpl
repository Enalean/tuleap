{if !empty($holdings)}
<h3>{translate text='Holdings at Other Libraries'}</h3>
<table cellpadding="2" cellspacing="0" border="0" class="citation" width="100%">
{foreach from=$holdings item=holding}
  <tr>
    <th colspan="2">
      {if !empty($holding.electronicAddress.text)}
      <a href="{$holding.electronicAddress.text|escape}">{$holding.physicalLocation|escape}</a>
      {else}
      {$holding.physicalLocation|escape}
      {/if}
    </th>
  </tr>
  <tr>
    <th>{translate text='Address'}: </th>
    <td>{$holding.physicalAddress.text|escape}</td>
  </tr>
  <tr>
    <th>{translate text='Copies'}: </th>
    <td>{$holding.holdingSimple.copiesSummary.copiesCount|escape}</td>
  </tr>
{/foreach}
</table>
{/if}