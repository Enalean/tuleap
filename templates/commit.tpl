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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h={$commit->GetHash()}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log&h={$commit->GetHash()}">log</a> | commit | {if $parent}<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}">commitdiff</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree}&hb={$commit->GetHash()}">tree</a>
   <br /><br />
 </div>
 <div class="title">
   {* Commit header *}
   {if $parent}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}" class="title">{$commit->GetTitle()}</a>
     <span class="refs">
     {assign var=heads value=$commit->GetHeads()}
     {if count($heads) > 0}
       {foreach name=head item=head from=$heads}
         <span class="head">
	   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog&h=refs/heads/{$head->GetName()}">{$head->GetName()}</a>
	 </span>
       {/foreach}
     {/if}
     {assign var=tags value=$commit->GetTags()}
     {if count($tags) > 0}
       {foreach name=tag item=tag from=$tags}
         <span class="tag">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tag&h={$tag->GetName()}">{$tag->GetName()}</a>
         </span>
       {/foreach}
     {/if}
     </span>
   {else}
     <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree}&hb={$commit->GetHash()}" class="title">{$commit->GetTitle()}</a>
   {/if}
 </div>
 <div class="title_text">
   {* Commit data *}
   <table cellspacing="0">
     <tr>
       <td>author</td>
       <td>{$commit->GetAuthorName()}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
       {assign var=hourlocal value=$commit->GetAuthorLocalEpoch()|date_format:"%H"}
       {if $hourlocal < 6}
       (<span class="latenight">{$commit->GetAuthorLocalEpoch()|date_format:"%R"}</span> {$commit->GetAuthorTimezone()})</td>
       {else}
       ({$commit->GetAuthorLocalEpoch()|date_format:"%R"} {$commit->GetAuthorTimezone()})</td>
       {/if}
     </tr>
     <tr>
       <td>committer</td>
       <td>{$commit->GetCommitterName()}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} ({$commit->GetCommitterLocalEpoch()|date_format:"%R"} {$commit->GetCommitterTimezone()})</td>
     </tr>
     <tr>
       <td>commit</td>
       <td class="monospace">{$commit->GetHash()}</td>
     <tr>
     <tr>
       <td>tree</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree}&hb={$commit->GetHash()}" class="list">{$tree}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&h={$tree}&hb={$commit->GetHash()}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=snapshot&h={$commit->GetHash()}">snapshot</a></td>
     </tr>
     {foreach from=$commit->GetParents() item=par}
       <tr>
         <td>parent</td>
	 <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$par->GetHash()}" class="list">{$par->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$par->GetHash()}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$commit->GetHash()}&hp={$par->GetHash()}">commitdiff</a></td>
       </tr>
     {/foreach}
   </table>
 </div>
 <div class="page_body">
   {foreach from=$commit->GetComment() item=line}
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
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].to_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a></td>
         <td><span class="newfile">[new {$difftreelines[difftree].to_filetype}{if $difftreelines[difftree].isreg} with mode: {$difftreelines[difftree].to_mode_cut}{/if}]</span></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].to_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}">blob</a></td>
       {elseif $difftreelines[difftree].status == "D"}
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].from_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a></td>
         <td><span class="deletedfile">[deleted {$difftreelines[difftree].from_filetype}]</span></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].from_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}">blob</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=history&h={$commit->GetHash()}&f={$difftreelines[difftree].file}">history</a></td>
       {elseif $difftreelines[difftree].status == "M" || $difftreelines[difftree].status == "T"}
         <td>
           {if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a>
           {else}
             <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].to_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a>
           {/if}
         </td>
         <td>{if $difftreelines[difftree].from_mode != $difftreelines[difftree].to_mode} <span class="changedfile">[changed{$difftreelines[difftree].modechange}]</span>{/if}</td>
         <td class="link">
           <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].to_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}">blob</a>{if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].file}">diff</a>{/if} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=history&h={$commit->GetHash()}&f={$difftreelines[difftree].file}">history</a></td>
       {elseif $difftreelines[difftree].status == "R"}
         <td><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].to_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].to_file}" class="list">{$difftreelines[difftree].to_file}</a></td>
         <td><span class="movedfile">[moved from <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].from_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].from_file}" class="list">{$difftreelines[difftree].from_file}</a> with {$difftreelines[difftree].similarity}% similarity{$difftreelines[difftree].simmodechg}]</span></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blob&h={$difftreelines[difftree].to_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].to_file}">blob</a>{if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$commit->GetHash()}&f={$difftreelines[difftree].to_file}">diff</a>{/if}</td>
       {/if}

     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

