{*
 * blame.tpl
 * gitphp: A PHP git repository browser
 * Component: Blame view template
 *
 * Copyright (C) 2010 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* If we managed to look up commit info, we have enough info to display the full header - othewise just use a simple header *}
 <div class="page_nav">
   {if $fullnav}
     <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hashbase}">tree</a><br />
     {if $file}
       <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}&f={$file}">plain</a> | 
       {if ($hashbase != "HEAD") && ($hashbase != $head)}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blame&hb=HEAD&f={$file}">HEAD</a>
       {else}
         HEAD
       {/if}
        | blame
       <br />
     {else}
       <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$hash}">plain</a><br />
     {/if}
   {else}
     <br /><br />
   {/if}
 </div>
 <div>
   {if $fullnav}
     <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}
     {if $hashbaseref}
       <span class="tag">{$hashbaseref}</span>
     {/if}
     </a>
   {else}
     <div class="title">{$hash}</div>
   {/if}
 </div>
 <div class="page_path">
   {* The path to the file, with directories broken into tree links *}
   <b>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project}]</a> / 
     {foreach from=$paths item=path name=paths}
       {if $smarty.foreach.paths.last}
         <a href="{$SCRIPT_NAME}?p={$project}&a=blob_plain&h={$path.tree}&f={$path.full}">{$path.short}</a>
       {else}
         <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
       {/if}
     {/foreach}
   </b>
 </div>
 <div class="page_body">
 	<table class="code">
	{counter name=linecount start=0 print=false}
	{foreach from=$blamedata item=blameitem}
		{cycle values="light,dark" assign=rowclass}
		{foreach from=$blameitem.lines name=linegroup item=blameline}
		{counter name=linecount assign=linenum}
		<tr class="{$rowclass}">
			<td class="num"><a id="l{$linenum}" href="#l{$linenum}" class="linenr">{$linenum}</a></td>
			<td class="date">{$blameitem.commitdata.authordate}</td>
			<td class="author">{if $smarty.foreach.linegroup.first}{$blameitem.commitdata.author}{/if}</td>
			<td>{if $blameitem.commit}{if $smarty.foreach.linegroup.first}<a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$blameitem.commit}" title="{$blameitem.commitdata.summary}">commit</a>{/if}{/if}</td>
			<td class="codeline">{$blameline}</td>
		</tr>
		{/foreach}
	{/foreach}
	</table>
 </div>

 {include file='footer.tpl'}
