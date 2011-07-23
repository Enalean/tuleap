{*
 *  tag.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{extends file='projectbase.tpl'}

{block name=main}

 {* Nav *}
 <div class="page_nav">
   {include file='nav.tpl' commit=$head treecommit=$head}
   <br /><br />
 </div>
 {* Tag data *}
 {assign var=object value=$tag->GetObject()}
 {assign var=objtype value=$tag->GetType()}
 <div class="title">
   {if $objtype == 'blob'}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$object->GetHash()}" class="title">{$tag->GetName()}</a>
   {else}
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()}" class="title">{$tag->GetName()}</a>
   {/if}
 </div>
 <div class="title_text">
   <table cellspacing="0">
     <tr>
       <td>{t}object{/t}</td>
       {if $objtype == 'commit'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=commit&amp;h={$object->GetHash()}">{t}commit{/t}</a></td>
       {elseif $objtype == 'tag'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$object->GetName()}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=tag&amp;h={$object->GetName()}">{t}tag{/t}</a></td>
       {elseif $objtype == 'blob'}
         <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$object->GetHash()}" class="list">{$object->GetHash()}</a></td>
         <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&amp;a=blob&amp;h={$object->GetHash()}">{t}blob{/t}</a></td>
       {/if}
     </tr>
     {if $tag->GetTagger()}
       <tr>
         <td>{t}author{/t}</td>
	 <td>{$tag->GetTagger()}</td>
       </tr>
       <tr>
         <td></td>
	 <td> {$tag->GetTaggerEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
	 {assign var=hourlocal value=$tag->GetTaggerLocalEpoch()|date_format:"%H"}
	 {if $hourlocal < 6}
	 (<span class="latenight">{$tag->GetTaggerLocalEpoch()|date_format:"%R"}</span> {$tag->GetTaggerTimezone()})
	 {else}
	 ({$tag->GetTaggerLocalEpoch()|date_format:"%R"} {$tag->GetTaggerTimezone()})
	 {/if}
         </td>
       </tr>
     {/if}
   </table>
 </div>
 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {foreach from=$tag->GetComment() item=line}
     {if strncasecmp(trim($line),'-----BEGIN PGP',14) == 0}
     <span class="pgpSig">
     {/if}
     {$line|htmlspecialchars|buglink:$bugpattern:$bugurl}<br />
     {if strncasecmp(trim($line),'-----END PGP',12) == 0}
     </span>
     {/if}
   {/foreach}
 </div>

{/block}
