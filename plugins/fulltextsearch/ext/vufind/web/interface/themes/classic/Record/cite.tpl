{if $citationCount < 1}
  {translate text="No citations are available for this record"}.
{else}
  <div style="text-align: left;">
    {if $apa}
      <b>{translate text="APA Citation"}</b>
      <p style="width: 95%; padding-left: 25px; text-indent: -25px;">
        {include file=$apa}
      </p>
    {/if}

    {if $mla}
      <b>{translate text="MLA Citation"}</b>
      <p style="width: 95%; padding-left: 25px; text-indent: -25px;">
        {include file=$mla}
      </p>
    {/if}
  </div>
  <div class="note">{translate text="Warning: These citations may not always be 100% accurate"}.</div>
{/if}