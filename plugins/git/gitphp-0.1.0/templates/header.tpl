{*
 *  header.tpl
 *  gitphp: A PHP git repository browser
 *  Component: Page header template
 *
 *  Copyright (C) 2006 Christopher Han <xiphux@gmail.com>
 *}
    <div class="page_header">
      <a href="http://www.kernel.org/pub/software/scm/git/docs/" title="git documentation">
        <img src="git-logo.png" width="72" height="27" alt="git" class="logo" />
      </a>
      <!-- <a href="index.php">projects</a> / -->
      &gt;
      {if $project && $validproject}
        <a href="{$SCRIPT_NAME}?p={$project}&a=summary">{$project}</a>
        {if $action && $validaction}
           / {$action}
        {/if}
        {if $enablesearch}
          <form method="get" action="index.php" enctype="application/x-www-form-urlencoded">
            <div class="search">
              <input type="hidden" name="p" value="{$project}" />
              <input type="hidden" name="a" value="search" />
              <input type ="hidden" name="h" value="{if $currentsearchhash}{$currentsearchhash}{else}HEAD{/if}" />
              <select name="st">
                <option {if $currentsearchtype == 'commit'}selected="selected"{/if} value="commit">commit</option>
                <option {if $currentsearchtype == 'author'}selected="selected"{/if} value="author">author</option>
                <option {if $currentsearchtype == 'committer'}selected="selected"{/if} value="committer">committer</option>
                {if $filesearch}
                  <option {if $currentsearchtype == 'file'}selected="selected"{/if} value="file">file</option>
                {/if}
              </select> search: <input type="text" name="s" {if $currentsearch}value="{$currentsearch}"{/if} />
            </div>
          </form>
        {/if}
      {/if}
    </div>
