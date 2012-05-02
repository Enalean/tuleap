{*
 *  project.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Project summary template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   summary | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$head}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$head}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree">tree</a>
   <br /><br />
 </div>
 <div class="title">&nbsp;</div>
 {* Project brief *}
 <table cellspacing="0">
   <tr><td>description</td><td>{$description}</td></tr>
   <tr><td>owner</td><td>{$owner}</td></tr>
   <tr><td>last change</td><td>{$lastchange}</td></tr>
   {if $cloneurl}
     <tr><td>clone url</td><td>{$cloneurl}</td></tr>
   {/if}
   {if $pushurl}
     <tr><td>push url</td><td>{$pushurl}</td></tr>
   {/if}
 </table>
 <div>
   <a class="title" href="{$SCRIPT_NAME}?p={$project}&a=shortlog">shortlog</a>
 </div>
 <table cellspacing="0">
   {* Recent revisions *}
   {section name=rev max=17 loop=$revlist}
     {if $smarty.section.rev.index == 16}
       <tr class="light">
         <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog">...</a></td>
       </tr>
     {else}
         <tr class="{cycle name=revs values="light,dark"}">
         <td><i>{$revlist[rev].commitage}</i></td>
         <td><i>{$revlist[rev].commitauthor}</i></td>
         <td>
           <a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev].commit}" class="list" {if $revlist[rev].title}title="{$revlist[rev].title}"{/if}><b>{$revlist[rev].title_short}
             {if $revlist[rev].commitref}
               <span class="tag">{$revlist[rev].commitref}</span>
             {/if}
           </b>
         </td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=commit&h={$revlist[rev].commit}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=commitdiff&h={$revlist[rev].commit}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h={$revlist[rev].commit}&hb={$revlist[rev].commit}">tree</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$revlist[rev].commit}&noheader=1">snapshot</a></td>
       </tr>
     {/if}
   {/section}
 </table>
 {if $taglist}
   {* Tags *}
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=tags" class="title">tags</a>
   </div>
   <table cellspacing="0">
     {section name=tag max=17 loop=$taglist}
       <tr class="{cycle name=tags values="light,dark"}">
         {if $smarty.section.tag.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=tags">...</a></td>
         {else}
           <td><i>{$taglist[tag].age_string}</i></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}" class="list"><b>{$taglist[tag].name}</b></a></td>
           <td>
             {if $taglist[tag].comment}
               <a class="list" href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}">{$taglist[tag].comment}</a>
             {/if}
           </td>
           <td class="link">
             {if $taglist[tag].type == "tag"}
   	       <a href="{$SCRIPT_NAME}?p={$project}&a=tag&h={$taglist[tag].id}">tag</a> | 
             {/if}
             <a href="{$SCRIPT_NAME}?p={$project}&a={$taglist[tag].reftype}&h={$taglist[tag].refid}">{$taglist[tag].reftype}</a>{if $taglist[tag].reftype == "commit"} | <a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/tags/{$taglist[tag].name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/tags/{$taglist[tag].name}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=snapshot&h={$taglist[tag].refid}&noheader=1">snapshot</a>{/if}
           </td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}
 {if $headlist}
   {* Heads *}
   <div>
     <a href="{$SCRIPT_NAME}?p={$project}&a=heads" class="title">heads</a>
   </div>
   <table cellspacing="0">
     {section name=head max=17 loop=$headlist}
       <tr class="{cycle name=heads values="light,dark"}">
         {if $smarty.section.head.index == 16}
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=heads">...</a></td>
         {else}
           <td><i>{$headlist[head].age_string}</i></td>
           <td><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head].name}" class="list"><b>{$headlist[head].name}</b></td>
           <td class="link"><a href="{$SCRIPT_NAME}?p={$project}&a=shortlog&h=refs/heads/{$headlist[head].name}">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=log&h=refs/heads/{$headlist[head].name}">log</a> | <a href="{$SCRIPT_NAME}?p={$project}&a=tree&h=refs/heads/{$headlist[head].name}&hb={$headlist[head].name}">tree</a></td>
         {/if}
       </tr>
     {/section}
   </table>
 {/if}

 {include file='footer.tpl'}

