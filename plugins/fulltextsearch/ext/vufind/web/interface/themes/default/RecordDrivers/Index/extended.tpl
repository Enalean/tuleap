<table cellpadding="2" cellspacing="0" border="0" class="citation" summary="{translate text='Description'}">
  {if !empty($extendedSummary)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Summary'}: </th>
    <td>
      {foreach from=$extendedSummary item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedDateSpan)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Published'}: </th>
    <td>
      {foreach from=$extendedDateSpan item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedNotes)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Item Description'}: </th>
    <td>
      {foreach from=$extendedNotes item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedPhysical)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Physical Description'}: </th>
    <td>
      {foreach from=$extendedPhysical item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedFrequency)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Publication Frequency'}: </th>
    <td>
      {foreach from=$extendedFrequency item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedPlayTime)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Playing Time'}: </th>
    <td>
      {foreach from=$extendedPlayTime item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedSystem)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Format'}: </th>
    <td>
      {foreach from=$extendedSystem item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAudience)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Audience'}: </th>
    <td>
      {foreach from=$extendedAudience item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAwards)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Awards'}: </th>
    <td>
      {foreach from=$extendedAwards item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedCredits)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Production Credits'}: </th>
    <td>
      {foreach from=$extendedCredits item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedBibliography)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Bibliography'}: </th>
    <td>
      {foreach from=$extendedBibliography item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedISBNs)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='ISBN'}: </th>
    <td>
      {foreach from=$extendedISBNs item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedISSNs)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='ISSN'}: </th>
    <td>
      {foreach from=$extendedISSNs item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedRelated)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Related Items'}: </th>
    <td>
      {foreach from=$extendedRelated item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedAccess)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Access'}: </th>
    <td>
      {foreach from=$extendedAccess item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {if !empty($extendedFindingAids)}
  {assign var=extendedContentDisplayed value=1}
  <tr valign="top">
    <th>{translate text='Finding Aid'}: </th>
    <td>
      {foreach from=$extendedFindingAids item=field name=loop}
        {$field|escape}<br>
      {/foreach}
    </td>
  </tr>
  {/if}

  {* Avoid errors if there were no rows above *}
  {if !$extendedContentDisplayed}
  <tr><td>&nbsp;</td></tr>
  {/if}
</table>