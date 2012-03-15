<table class="citation">
  {foreach from=$details key='field' item='values'}
    <tr>
      <th>{$field|escape}</th>
      <td>
        <div style="width: 500px; overflow: auto;">
        {foreach from=$values item='value'}
          {$value|escape}<br />
        {/foreach}
        </div>
      </td>
    </tr>
  {/foreach}
</table>