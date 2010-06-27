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
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=summary">{$resources->GetResource('summary')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$head}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$head}">{$resources->GetResource('commitdiff')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree&hb={$head}">{$resources->GetResource('tree')}</a>
   <br /><br />
 </div>
 {* Tag data *}
 {assign var=object value=$tag->GetObject()}
 {assign var=objtype value=$tag->GetType()}
 <div class="title">
   <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$object->GetHash()}" class="title">{$tag->GetName()}</a>
 </div>
 <div class="title_text">
   <table cellspacing="0">
     <tr>
       <td>object</td>
       <td class="monospace"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$objtype}&h={$object->GetHash()}" class="list">{$object->GetHash()}</a></td>
       <td class="link"><a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a={$objtype}&h={$object->GetHash()}">{$resources->GetResource($objtype)}</a></td>
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

