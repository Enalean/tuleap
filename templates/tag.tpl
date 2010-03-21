{*
 *  tag.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Tag view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}

 {include file='header.tpl'}

 {* Nav *}
 <div class="page_nav">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=summary">summary</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=shortlog">shortlog</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=log">log</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$head}">commit</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commitdiff&h={$head}">commitdiff</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=tree&hb={$head}">tree</a>
   <br /><br />
 </div>
 {* Tag data *}
 {assign var=object value=$tag->GetObject()}
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a=commit&h={$object->GetHash()}" class="title">{$tag->GetName()}</a>
 </div>
 <div class="title_text">
   <table cellspacing="0">
     <tr>
       <td>object</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a={$tag->GetType()}&h={$object->GetHash()}" class="list">{$object->GetHash()}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()}&a={$tag->GetType()}&h={$object->GetHash()}">{$tag->GetType()}</a></td>
     </tr>
     {if $tag->GetTagger()}
       <tr>
         <td>author</td>
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
   {foreach from=$tag->GetComment() item=line}
     {$line}<br />
   {/foreach}
 </div>

 {include file='footer.tpl'}

