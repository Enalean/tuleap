{if $saved}
  <div class="warning">
    {if $bytesWritten}
      File saved successfully -- {$bytesWritten} bytes written.
    {else}
      Problem saving file -- check write permissions on server.
    {/if}
  </div>
{/if}
