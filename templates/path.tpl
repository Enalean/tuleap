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
		<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$pathobjectcommit->GetHash()}&h={$pathobjecttree->GetHash()}"><strong>[{$project->GetProject()}]</strong></a> / 
		{foreach from=$pathobject->GetPathTree() item=pathtreepiece}
			<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$pathobjectcommit->GetHash()}&h={$pathtreepiece->GetHash()}&f={$pathtreepiece->GetPath()}"><strong>{$pathtreepiece->GetName()}</strong></a> / 
		{/foreach}
		{if $pathobject instanceof GitPHP_Blob}
			{if $target == 'blobplain'}
				<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob_plain&h={$pathobject->GetHash()}&hb={$pathobjectcommit->GetHash()}&f={$pathobject->GetPath()}"><strong>{$pathobject->GetName()}</strong></a>
			{elseif $target == 'blob'}
				<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=blob&h={$pathobject->GetHash()}&hb={$pathobjectcommit->GetHash()}&f={$pathobject->GetPath()}"><strong>{$pathobject->GetName()}</strong></a>
			{else}
				<strong>{$pathobject->GetName()}</strong>
			{/if}
		{elseif $pathobject->GetName()}
			{if $target == 'tree'}
				<a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$pathobjectcommit->GetHash()}&h={$pathobject->GetHash()}&f={$pathobject->GetPath()}"><strong>{$pathobject->GetName()}</strong></a> / 
			{else}
				<strong>{$pathobject->GetName()}</strong> / 
			{/if}
		{/if}
	{else}
		&nbsp;
	{/if}
</div>
