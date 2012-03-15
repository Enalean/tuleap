<a href="{$openUrlBase|escape}?{$openUrl|escape}" class="fulltext"{if $openUrlWindow} onClick="window.open('{$openUrlBase|escape}?{$openUrl|escape}', 'openurl', '{$openUrlWindow|escape}'); return false;"{/if}>
  {if $openUrlGraphic}
    <img src="{$openUrlGraphic|escape}" alt="{translate text='Get full text'}" style="{if $openUrlGraphicWidth}width:{$openUrlGraphicWidth|escape}px;{/if}{if $openUrlGraphicHeight}height:{$openUrlGraphicHeight|escape}px;{/if}" />
  {else}
    {translate text='Get full text'}
  {/if}
</a>

