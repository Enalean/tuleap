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
   {* i18n: summary = summary *}
   {* i18n: shortlog = shortlog *}
   {* i18n: log = log *}
   {* i18n: commit = commit *}
   {* i18n: commitdiff = commitdiff *}
   {* i18n: tree = tree *}
   <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$localize.summary}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h={$hash}">{$localize.shortlog}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h={$hash}">{$localize.log}</a> | {$localize.commit} | {if $parent}<a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}">{$localize.commitdiff}</a> | {/if}<a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">{$localize.tree}</a>
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
       {* i18n: author = author *}
       <td>{$localize.author}</td>
       <td>{$author}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$adrfc2822} ({if $adhourlocal < 6}<span class="latenight">{/if}{$adhourlocal}:{$adminutelocal}{if $adhourlocal < 6}</span>{/if} {$adtzlocal})</td>
     </tr>
     <tr>
       {* i18n: committer = committer *}
       <td>{$localize.committer}</td>
       <td>{$committer}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$cdrfc2822} ({$cdhourlocal}:{$cdminutelocal} {$cdtzlocal})</td>
     </tr>
     <tr>
       {* i18n: commit = commit *}
       <td>{$localize.commit}</td>
       <td class="monospace">{$id}</td>
     <tr>
     <tr>
       {* i18n: tree = tree *}
       <td>{$localize.tree}</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}" class="list">{$tree}</a></td>
       {* i18n: snapshot = snapshot *}
       {* i18n: tree = tree *}
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$tree}&hb={$hash}">{$localize.tree}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$hash}">{$localize.snapshot}</a></td>
     </tr>
     {foreach from=$parents item=par}
       <tr>
         {* i18n: parent = parent *}
         <td>{$localize.parent}</td>
	 <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}" class="list">{$par}</a></td>
	 {* i18n: commit = commit *}
	 {* i18n: commitdiff = commitdiff *}
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$par}">{$localize.commit}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$hash}&hp={$par}">{$localize.commitdiff}</a></td>
       </tr>
     {/foreach}
   </table>
 </div>
 <div class="page_body">
   {foreach from=$comment item=line}
     {$line}<br />
   {/foreach}
 </div>
 <div class="list_head">
   {if $difftreesize > 11}
     {* i18n: fileschanged = %1$d files changed *}
     {$localize.fileschanged|sprintf:$difftreesize}:
   {/if}
 </div>
 <table cellspacing="0">
   {* Loop and show files changed *}
   {section name=difftree loop=$difftreelines}
     <tr class="{cycle values="light,dark"}">
	 
       {if $difftreelines[difftree].status == "A"}
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a></td>
	 {if $difftreelines[difftree].isreg}
	   {* i18n: newobjectwithmode = new %1$s with mode: %2$s *}
           <td><span class="newfile">[{$localize.newobjectwithmode|sprintf:$difftreelines[difftree].to_filetype_localized:$difftreelines[difftree].to_mode_cut}]</span></td>
	 {else}
	   {* i18n: newobject = new %1$s *}
           <td><span class="newfile">[{$localize.newobject|sprintf:$difftreelines[difftree].to_filetype_localized}]</span></td>
	 {/if}
	 {* i18n: blob = blob *}
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}">{$localize.blob}</a></td>
       {elseif $difftreelines[difftree].status == "D"}
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a></td>
	 {* i18n: deletedobject = deleted %1$s *}
         <td><span class="deletedfile">[{$localize.deletedobject|sprintf:$difftreelines[difftree].from_filetype_localized}]</span></td>
	 {* i18n: blob = blob *}
	 {* i18n: history = history *}
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}">{$localize.blob}</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$difftreelines[difftree].file}">{$localize.history}</a></td>
       {elseif $difftreelines[difftree].status == "M" || $difftreelines[difftree].status == "T"}
         <td>
           {if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id}
             <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a>
           {else}
             <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}" class="list">{$difftreelines[difftree].file}</a>
           {/if}
         </td>
         <td>
	   {if $difftreelines[difftree].from_mode != $difftreelines[difftree].to_mode}
	     <span class="changedfile">
	       {if $difftreelines[difftree].typechange}
	         {* i18n: changedobjecttype = changed from %1$s to %2$s *}
	         [{$localize.changedobjecttype|sprintf:$difftreelines[difftree].from_filetype_localized:$difftreelines[difftree].to_filetype_localized}]
	       {/if}
	       {if $difftreelines[difftree].modechange}
	         {* i18n: changedobjectmode = changed mode: %1$s *}
	         [{$localize.changedobjectmode|sprintf:$difftreelines[difftree].modechange}]
	       {/if}
	     </span>
	   {/if}
	 </td>
         <td class="link">
	   {* i18n: blob = blob *}
	   {* i18n: diff = diff *}
	   {* i18n: history = history *}
           <a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].file}">{$localize.blob}</a>{if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].file}">{$localize.diff}</a>{/if} | <a href="{$SCRIPT_NAME}?p={$project}&a=history&h={$hash}&f={$difftreelines[difftree].file}">{$localize.history}</a></td>
       {elseif $difftreelines[difftree].status == "R"}
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].to_file}" class="list">{$difftreelines[difftree].to_file}</a></td>
	 {capture name='oldfile' assign='oldfile'}<a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].from_file}" class="list">{$difftreelines[difftree].from_file}</a>{/capture}
         {if $difftreelines[difftree].simmodechg}
           {* i18n: movedobjectwithsimilaritymodechange = moved from %1$s with %2$d%% similarity, mode: %3$s *}
           <td><span class="movedfile">[{$localize.movedobjectwithsimilaritymodechange|sprintf:$oldfile:$difftreelines[difftree].similarity:$difftreelines[difftree].simmodechg}]</span></td>
	 {else}
           {* i18n: movedobjectwithsimilarity = moved from %1$s with %2$d%% similarity *}
           <td><span class="movedfile">[{$localize.movedobjectwithsimilarity|sprintf:$oldfile:$difftreelines[difftree].similarity}]</span></td>
	 {/if}
	 {* i18n: blob = blob *}
	 {* i18n: diff = diff *}
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=blob&h={$difftreelines[difftree].to_id}&hb={$hash}&f={$difftreelines[difftree].to_file}">{$localize.blob}</a>{if $difftreelines[difftree].to_id != $difftreelines[difftree].from_id} | <a href="{$SCRIPT_NAME}?p={$project}&a=blobdiff&h={$difftreelines[difftree].to_id}&hp={$difftreelines[difftree].from_id}&hb={$hash}&f={$difftreelines[difftree].to_file}">{$localize.diff}</a>{/if}</td>
       {/if}

     </tr>
   {/section}
 </table>

 {include file='footer.tpl'}

