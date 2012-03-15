{if $tocTemplate}
  <b>{translate text='Table of Contents'}: </b>
  {include file=$tocTemplate}
{else}
  {translate text="Table of Contents unavailable"}.
{/if}
