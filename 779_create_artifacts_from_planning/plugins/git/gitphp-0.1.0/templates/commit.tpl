{*
 *  commit.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commit view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 <div class="page_nav">
   {* Nav *}
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">log</a> | commit | {if $parent}<a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">commitdiff</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a>
   <br /><br />
 </div>
 <div>
   {* Commit header *}
   {if $parent}
     <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}" class="title">{$title}
     {if $commitref}
       <span class="tag">{$commitref}</span>
     {/if}
     </a>
   {else}
     <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="title">{$title}</a>
   {/if}
 </div>
 <div class="title_text">
   {* Commit data *}
   <table cellspacing="0">
     <tr>
       <td>author</td>
       <td>{$author}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$adrfc2822} ({if $adhourlocal < 6}<span class="latenight">{/if}{$adhourlocal}:{$adminutelocal}{if $adhourlocal < 6}</span>{/if} {$adtzlocal})</td>
     </tr>
     <tr>
       <td>committer</td>
       <td>{$committer}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$cdrfc2822} ({$cdhourlocal}:{$cdminutelocal} {$cdtzlocal})</td>
     </tr>
     <tr>
       <td>commit</td>
       <td class="monospace">{$id}</td>
     <tr>     
     <tr>
       <td>tree</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="list">{$tree}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$hash}&noheader=1">snapshot</a></td>
     </tr>
     {foreach from=$parents item=par}
       <tr>
         <td>parent</td>
	 <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}" class="list">{$par}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}&hp={$par}">commitdiff</a></td>
       </tr>
     {/foreach}
      <tr>
       <td>reference</td>
       <td class="monospace">git #{$repository}/{$id}</td>
     <tr>
   </table>
 </div>
 <div class="page_body">
   {foreach from=$comment item=line}
     {$line}<br />
   {/foreach}
 </div>
 <div class="list_head">
   {if $difftreesize > 11}
     {$difftreesize} files changed:
   {/if}
 </div>
 <table cellspacing="0">
   {* Loop and show files changed *}
   {section name=difftree loop=$difftreelines}
     <tr class="{cycle values="light,dark"}">
	 
       {if $difftreelines[difftree].status == "A"}
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a></td>
         <td><span class="newfile">[new {$difftreelines[difftree].to_filetype}{if $difftreelines[difftree].isreg} with mode: {$difftreelines[difftree].to_mode_cut}{/if}]</span></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}">blob</a></td>
       {elseif $difftreelines[difftree].status == "D"}
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a></td>
         <td><span class="deletedfile">[deleted {$difftreelines[difftree].from_filetype}]</span></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$difftreelines[difftree].file}">history</a></td>
       {elseif $difftreelines[difftree].status == "M" || $difftreelines[difftree].status == "T"}
         <td>
           {if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id}
             <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a>
           {else}
             <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a>
           {/if}
         </td>
         <td>{if $difftreelines[difftree].from_mode != $difftreelines[difftree].to_mode} <span class="changedfile">[changed{$difftreelines[difftree].modechange}]</span>{/if}</td>
         <td class="link">
           <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}">blob</a>{if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}">diff</a>{/if} | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$difftreelines[difftree].file}">history</a></td>
       {elseif $difftreelines[difftree].status == "R"}
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].to_file}" class="list">{$difftreelines[difftree].to_file}</a></td>
         <td><span class="movedfile">[moved from <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].from_file}" class="list">{$difftreelines[difftree].from_file}</a> with {$difftreelines[difftree].similarity}% similarity{$difftreelines[difftree].simmodechg}]</span></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].to_file}">blob</a>{if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].to_file}">diff</a>{/if}</td>
       {/if}

     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

