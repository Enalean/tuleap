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
   {$resources->GetResource('summary')} | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=shortlog">{$resources->GetResource('shortlog')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=log">{$resources->GetResource('log')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commit&h={$head->GetHash()}">{$resources->GetResource('commit')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=commitdiff&h={$head->GetHash()}">{$resources->GetResource('commitdiff')}</a> | <a href="{$SCRIPT_NAME}?p={$project->GetProject()|urlencode}&a=tree">{$resources->GetResource('tree')}</a>
   <br /><br />
 </div>

 {include file='title.tpl'}

 {* Project brief *}
 <table cellspacing="0">
   <tr><td>{$resources->GetResource('description')}</td><td>{$project->GetDescription()}</td></tr>
   <tr><td>{$resources->GetResource('owner')}</td><td>{$project->GetOwner()}</td></tr>
   <tr><td>{$resources->GetResource('last change')}</td><td>{$head->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"}</td></tr>
   {if $project->GetCloneUrl()}
     <tr><td>{$resources->GetResource('clone url')}</td><td>{$project->GetCloneUrl()}</td></tr>
   {/if}
   {if $project->GetPushUrl()}
     <tr><td>{$resources->GetResource('push url')}</td><td>{$project->GetPushUrl()}</td></tr>
   {/if}
 </table>

 {include file='title.tpl' target='shortlog'}

 {include file='shortloglist.tpl' source='summary'}
 
 {if $taglist}
  
  {include file='title.tpl' target='tags'}

  {include file='taglist.tpl' max=17}
   
 {/if}

 {if $headlist}

  {include file='title.tpl' target='heads'}

  {include file='headlist.tpl' max=17}

 {/if}

 {include file='footer.tpl'}

