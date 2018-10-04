<div class="git-repository-diff-side-by-side">
    {if $filediff->GetStatus() == 'D'}
        {assign var=delblob value=$filediff->GetFromBlob()}
        {foreach from=$delblob->GetData(true) item=blobline}
            <div class="git-repository-diff-side-by-side-line">
                <div class="git-repository-diff-line-minus">{$blobline|escape}</div>
                <div></div>
            </div>
        {/foreach}
    {elseif $filediff->GetStatus() == 'A'}
        {assign var=newblob value=$filediff->GetToBlob()}
        {foreach from=$newblob->GetData(true) item=blobline}
            <div class="git-repository-diff-side-by-side-line">
                <div></div>
                <div class="git-repository-diff-line-plus">{$blobline|escape}</div>
            </div>
        {/foreach}
    {else}
        {foreach from=$diffsplit item=lineinfo}
            {if $lineinfo[0]=='added'}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-plus">{$blobline|escape}</div>
                    <div class="git-repository-diff-line-empty"></div>
                </div>
            {elseif $lineinfo[0]=='deleted'}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-minus">{$blobline|escape}</div>
                </div>
            {elseif $lineinfo[0]=='modified'}
                <div class="git-repository-diff-side-by-side-line">
                    <div class="git-repository-diff-line-minus">{$lineinfo[1]|escape}</div>
                    <div class="git-repository-diff-line-plus">{$lineinfo[2]|escape}</div>
                </div>
            {else}
                <div class="git-repository-diff-side-by-side-line">
                    <div>{if $lineinfo[1]}{$lineinfo[1]|escape}{/if}</div>
                    <div>{if $lineinfo[2]}{$lineinfo[2]|escape}{/if}</div>
                </div>
            {/if}
        {/foreach}
    {/if}
</div>
