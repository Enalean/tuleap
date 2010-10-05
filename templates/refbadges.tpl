{*
 * Refbadges
 *
 * Ref badges template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}

<span class="refs">
	{foreach from=$commit->GetHeads() item=commithead}
		<span class="head">
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=shortlog&amp;h=refs/heads/{$commithead->GetName()}">{$commithead->GetName()}</a>
		</span>
	{/foreach}
	{foreach from=$commit->GetTags() item=committag}
		<span class="tag">
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$committag->GetName()}" {if !$committag->LightTag()}class="tagTip"{/if}>{$committag->GetName()}</a>
		</span>
	{/foreach}
</span>
