{foreach from=$recordSet item=record name="recordLoop"}
  {* This is raw HTML -- do not escape it: *}
  {$record}
{/foreach}
