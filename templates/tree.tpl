{*
 *  tree.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tree view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 {if $fullnav}
   <div class="page_nav">
     <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hashbase}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hashbase}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hashbase}">commitdiff</a> | tree<br /><br />
   </div>
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$hashbase}" class="title">{$title}
       {if $hashbaseref}
         <span class="tag">{$hashbaseref}</span>
       {/if}
     </a>
   </div>
 {else}
   <div class="page_nav"><br /><br /></div>
   <div class="title">{$hash}</div>
 {/if}
 {* Path *}
 <div class="page_path">
   <b>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$hashbase}">[{$project}]</a> / 
     {foreach from=$paths item=path}
       <a href="{$SCRIPT_NAME}?p={$project}&a=tree&hb={$hashbase}&h={$path.tree}&f={$path.full}">{$path.short}</a> / 
     {/foreach}
   </b>
 </div>
 <div class="page_body">
   {* List files *}
   <table cellspacing="0">
     {section name=tree loop=$treelines}
       <tr class="{cycle values="light,dark"}">
         <td class="monospace">{$treelines[tree].filemode}</td>
         {if $treelines[tree].type == "blob"}
           <td class="list">
             <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$treelines[tree].hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$treelines[tree].name}" class="list">{$treelines[tree].name}</a>
	   </td>
           <td class="link">
	     <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$treelines[tree].hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$treelines[tree].name}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hashbase}&f={if $base}{$base}{/if}{$treelines[tree].name}">history</a>
	   </td>
         {elseif $treelines[tree].type == "tree"}
           <td class="list">
             <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$treelines[tree].hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$treelines[tree].name}">{$treelines[tree].name}</a>
	   </td>
           <td class="link">
	     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$treelines[tree].hash}{if $hashbase}&hb={$hashbase}{/if}&f={if $base}{$base}{/if}{$treelines[tree].name}">tree</a>
	   </td>
         {/if}
       </tr>
     {/section}
   </table>
 </div>

 {include file='footer.tpl'}

