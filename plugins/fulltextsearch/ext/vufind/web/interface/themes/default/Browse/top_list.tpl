{* This list is shown on every browse page, so this is the best place to stuff
   a text string for extraction by the Javascript: *}
<input type="hidden" id="browseLoadMessage" value="{translate text='Loading'}" />

<ul class="browse" id="list1">
{foreach from=$browseOptions item=currentOption}
  <li {if $currentOption.action == $currentAction} class="active"{/if}>
    <a href="{$url}/Browse/{$currentOption.action}">{translate text=$currentOption.description}</a>
  </li>
{/foreach}
</ul>
