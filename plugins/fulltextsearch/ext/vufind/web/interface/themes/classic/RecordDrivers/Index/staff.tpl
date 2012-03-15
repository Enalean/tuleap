<table class="citation">
  {foreach from=$details key='field' item='values'}
    <tr>
      <th>{$field|escape}</th>
      <td>
        {* Line height adjusted on this <div> to prevent unwanted vertical
           scroll bars in Safari: *}
        <div style="width: 500px; line-height: 1.37em; overflow: auto;">
        {foreach from=$values item='value'}
          {$value|escape}<br />
        {/foreach}
        </div>
      </td>
    </tr>
  {/foreach}
</table>