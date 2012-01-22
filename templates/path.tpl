{*
 * Path
 *
 * Path template
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2010 Christopher Han
 * @package GitPHP
 * @subpackage Template
 *}
<div class="page_path">
	{if $pathobject}
		{assign var=pathobjectcommit value=$pathobject->GetCommit()}
		{assign var=pathobjecttree value=$pathobjectcommit->GetTree()}
		<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$pathobjectcommit->GetHash()}&amp;h={$pathobjecttree->GetHash()}"><strong>[{$project->GetProject()}]</strong></a> / 
		{foreach from=$pathobject->GetPathTree() item=pathtreepiece}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$pathobjectcommit->GetHash()}&amp;h={$pathtreepiece->GetHash()}&amp;f={$pathtreepiece->GetPath()|escape:'url'}"><strong>{$pathtreepiece->GetName()|escape}</strong></a> / 
		{/foreach}
		{if $pathobject instanceof GitPHP_Blob}
			{if $target == 'blobplain'}
				<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob_plain&amp;h={$pathobject->GetHash()}&amp;hb={$pathobjectcommit->GetHash()}&amp;f={$pathobject->GetPath()|escape:'url'}"><strong>{$pathobject->GetName()|escape}</strong></a>
			{elseif $target == 'blob'}
				<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$pathobject->GetHash()}&amp;hb={$pathobjectcommit->GetHash()}&amp;f={$pathobject->GetPath()|escape:'url'}"><strong>{$pathobject->GetName()|escape}</strong></a>
			{else}
				<strong>{$pathobject->GetName()|escape}</strong>
			{/if}
		{elseif $pathobject->GetName()}
			{if $target == 'tree'}
				<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tree&amp;hb={$pathobjectcommit->GetHash()}&amp;h={$pathobject->GetHash()}&amp;f={$pathobject->GetPath()|escape:'url'}"><strong>{$pathobject->GetName()|escape}</strong></a> / 
			{else}
				<strong>{$pathobject->GetName()|escape}</strong> / 
			{/if}
		{/if}
	{else}
		&nbsp;
	{/if}
</div>
