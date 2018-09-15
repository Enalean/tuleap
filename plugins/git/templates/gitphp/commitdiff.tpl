{*
 *  commitdiff.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Commitdiff view template
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *}
{include file='header.tpl'}

{php}
    $purifier = Codendi_HTMLPurifier::instance();
{/php}

 {* Nav *}
 <div class="page_nav">
   {if $commit}
   {assign var=tree value=$commit->GetTree()}
   {/if}
   {include file='nav.tpl' current='commitdiff' logcommit=$commit treecommit=$commit}
   <br />
   {if $sidebyside}
   <a href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$commit->GetHash()|urlencode}{if $hashparent}&amp;hp={$hashparent|urlencode}{/if}&amp;o=unified">{t}unified{/t}</a>
   {else}
   <a href="{$SCRIPT_NAME}?a=commitdiff&amp;h={$commit->GetHash()|urlencode}{if $hashparent}&amp;hp={$hashparent}{/if}&amp;o=sidebyside">{t}side by side{/t}</a>
   {/if}
   | <a href="{$SCRIPT_NAME}?a=commitdiff_plain&amp;h={$commit->GetHash()|urlencode}{if $hashparent}&amp;hp={$hashparent|urlencode}{/if}&noheader=1">{t}plain{/t}</a>
 </div>

 {include file='title.tpl' titlecommit=$commit}
 <div class="title_text">
   {* Commit data *}
   <table cellspacing="0">
     <tr>
       <td>{t}author{/t}</td>
       <td>{$commit->GetAuthorName()|escape}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetAuthorEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} 
       {assign var=hourlocal value=$commit->GetAuthorLocalEpoch()|date_format:"%H"}
       {if $hourlocal < 6}
       (<span class="latenight">{$commit->GetAuthorLocalEpoch()|date_format:"%R"}</span> {$commit->GetAuthorTimezone()|escape})</td>
       {else}
       ({$commit->GetAuthorLocalEpoch()|date_format:"%R"} {$commit->GetAuthorTimezone()|escape})</td>
       {/if}
     </tr>
     <tr>
       <td>{t}committer{/t}</td>
       <td>{$commit->GetCommitterName()|escape}</td>
     </tr>
     <tr>
       <td></td>
       <td> {$commit->GetCommitterEpoch()|date_format:"%a, %d %b %Y %H:%M:%S %z"} ({$commit->GetCommitterLocalEpoch()|date_format:"%R"} {$commit->GetCommitterTimezone()|escape})</td>
     </tr>
   </table>
 </div>
 <div class="page_body">
   {assign var=bugpattern value=$project->GetBugPattern()}
   {assign var=bugurl value=$project->GetBugUrl()}
   {foreach from=$commit->GetComment() item=line}
     {if strncasecmp(trim($line),'Signed-off-by:',14) == 0}
	<span class="signedOffBy">
            {php}
                echo $purifier->purify(
                    $this->get_template_vars('line'),
                    CODENDI_PURIFIER_BASIC_NOBR,
                    HTTPRequest::instance()->getProject()->getID()
                );
            {/php}
        </span>
     {else}
        {php}
            echo $purifier->purify(
                $this->get_template_vars('line'),
                CODENDI_PURIFIER_BASIC_NOBR,
                HTTPRequest::instance()->getProject()->getID()
            );
        {/php}
     {/if}
     <br />
   {/foreach}
   <br />

   {if $sidebyside && ($treediff->Count() > 1)}
    <div class="commitDiffSBS">

     <div class="SBSTOC">
       <ul>
       <li class="listcount">
       {t count=$treediff->Count() 1=$treediff->Count() plural="%1 files changed:"}%1 file changed:{/t} <a href="#" class="showAll">{t}(show all){/t}</a></li>
       {foreach from=$treediff item=filediff}
       <li>
       <a href="#{$filediff->GetFromHash()|escape}_{$filediff->GetToHash()|escape}" class="SBSTOCItem">
       {if $filediff->GetStatus() == 'A'}
         {if $filediff->GetToFile()}{$filediff->GetToFile()|escape}{else}{$filediff->GetToHash()|escape}{/if} {t}(new){/t}
       {elseif $filediff->GetStatus() == 'D'}
         {if $filediff->GetFromFile()}{$filediff->GetFromFile()|escape}{else}{$filediff->GetToFile()|escape}{/if} {t}(deleted){/t}
       {elseif $filediff->GetStatus() == 'M'}
         {if $filediff->GetFromFile()}
	   {assign var=fromfilename value=$filediff->GetFromFile()}
	 {else}
	   {assign var=fromfilename value=$filediff->GetFromHash()}
	 {/if}
	 {if $filediff->GetToFile()}
	   {assign var=tofilename value=$filediff->GetToFile()}
	 {else}
	   {assign var=tofilename value=$filediff->GetToHash()}
	 {/if}
	 {$fromfilename|escape}{if $fromfilename != $tofilename} -&gt; {$tofilename|escape}{/if}
       {/if}
       </a>
       </li>
       {/foreach}
       </ul>
     </div>

     <div class="SBSContent">
   {/if}

   {* Diff each file changed *}
   {foreach from=$treediff item=filediff}
     <div class="diffBlob" id="{$filediff->GetFromHash()|escape}_{$filediff->GetToHash()|escape}">
     <div class="diff_info">
     {if ($filediff->GetStatus() == 'D') || ($filediff->GetStatus() == 'M')}
       {assign var=localfromtype value=$filediff->GetFromFileType(1)}
       {$localfromtype}:<a href="{$SCRIPT_NAME}?a=blob&amp;h={$filediff->GetFromHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}{if $filediff->GetFromFile()}&amp;f={$filediff->GetFromFile()|urlencode}{/if}">{if $filediff->GetFromFile()}a/{$filediff->GetFromFile()|escape}{else}{$filediff->GetFromHash()|escape}{/if}</a>
       {if $filediff->GetStatus() == 'D'}
         {t}(deleted){/t}
       {/if}
     {/if}

     {if $filediff->GetStatus() == 'M'}
       -&gt;
     {/if}

     {if ($filediff->GetStatus() == 'A') || ($filediff->GetStatus() == 'M')}
       {assign var=localtotype value=$filediff->GetToFileType(1)}
       {$localtotype}:<a href="{$SCRIPT_NAME}?a=blob&amp;h={$filediff->GetToHash()|urlencode}&amp;hb={$commit->GetHash()|urlencode}{if $filediff->GetToFile()}&amp;f={$filediff->GetToFile()|urlencode}{/if}">{if $filediff->GetToFile()}b/{$filediff->GetToFile()|escape}{else}{$filediff->GetToHash()|escape}{/if}</a>

       {if $filediff->GetStatus() == 'A'}
         {t}(new){/t}
       {/if}
     {/if}
     </div>
     {if $sidebyside}
        {include file='filediffsidebyside.tpl' diffsplit=$filediff->GetDiffSplit()}
     {else}
        {include file='filediff.tpl' diff=$filediff->GetDiff('', true, true)}
     {/if}
     </div>
   {/foreach}

   {if $sidebyside && ($treediff->Count() > 1)}
     </div>
     <div class="SBSFooter"></div>

    </div>
   {/if}


 </div>

 {include file='footer.tpl'}

