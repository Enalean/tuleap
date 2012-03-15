<table class="citation">
  {foreach from=$details key='field' item='values'}
    <tr>
      <th>{$field|escape}</th>
      <td>
        {* Hard-coded width should be adjusted for mobile theme, but since
           staff view is not accessible there yet, we can worry about it
           later. *}
        <div style="width: 500px; overflow: auto;">
        {foreach from=$values item='value'}
          {$value|escape}<br />
        {/foreach}
        </div>
      </td>
    </tr>
  {/foreach}
</table>