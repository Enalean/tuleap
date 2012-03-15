<form name="addForm" action="">
  {foreach from=$recordSet item=record name="recordLoop"}
    <div class="result {if ($smarty.foreach.recordLoop.iteration % 2) == 0}alt {/if}record{$smarty.foreach.recordLoop.iteration}">
      {* This is raw HTML -- do not escape it: *}
      {$record}
    </div>
  {/foreach}
</form>

<script type="text/javascript">
  doGetStatuses({literal}{{/literal}
    available: '<span class="available">{translate text='Available'}<\/span>',
    unavailable: '<span class="checkedout">{translate text='Checked Out'}<\/span>',
    unknown: '<span class="unknown">{translate text='Unknown'}<\/span>',
    reserve: '{translate text='on_reserve'}'
  {literal}}{/literal});
  {if $user}
  doGetSaveStatuses();
  {/if}
</script>
