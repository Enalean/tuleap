<div class="git-repository-diff-side-by-side">
    {if $filediff->GetStatus() == 'D'}
        {assign var=delblob value=$filediff->GetFromBlob()}
        {foreach from=$delblob->GetData(true) item=blobline}
            <div class="git-repository-diff-side-by-side-line">
                <div class="git-repository-diff-line-left git-repository-diff-line-deleted">{$blobline|escape}</div>
                <div class="git-repository-diff-line-right"></div>
            </div>
        {/foreach}
    {elseif $filediff->GetStatus() == 'A'}
        {assign var=newblob value=$filediff->GetToBlob()}
        {foreach from=$newblob->GetData(true) item=blobline}
            <div class="git-repository-diff-side-by-side-line">
                <div class="git-repository-diff-line-left"></div>
                <div class="git-repository-diff-line-right git-repository-diff-line-added">{$blobline|escape}</div>
            </div>
        {/foreach}
    {else}
        {foreach from=$diffsplit item=lineinfo}
            {if $lineinfo[0]=='added'}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-left"></div>
                    <div class="git-repository-diff-line-right git-repository-diff-line-added">{$lineinfo[2]|escape}</div>
                </div>
            {elseif $lineinfo[0]=='deleted'}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-left git-repository-diff-line-deleted">{$lineinfo[1]|escape}</div>
                    <div class="git-repository-diff-line-right"></div>
                </div>
            {elseif $lineinfo[0]=='modified'}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-left git-repository-diff-line-deleted">{$lineinfo[1]|escape}</div>
                    <div class="git-repository-diff-line-right git-repository-diff-line-added">{$lineinfo[2]|escape}</div>
                </div>
            {else}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-left">{$lineinfo[1]|escape}</div>
                    <div class="git-repository-diff-line-right">{$lineinfo[2]|escape}</div>
                </div>
            {/if}
        {/foreach}
    {/if}
</div>
