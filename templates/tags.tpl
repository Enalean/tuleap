{*
 *  tags.tpl
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

{include file='title.tpl' target='summary'}
 
 {* Display tags *}

 {include file='taglist.tpl'}

 {include file='footer.tpl'}

