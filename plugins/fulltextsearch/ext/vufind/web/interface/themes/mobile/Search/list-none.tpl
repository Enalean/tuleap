
<script language="JavaScript" type="text/javascript" src="{$path}/services/Search/ajax.js"></script>    

<div id="bd">
  <div id="yui-main" class="content">
    <div class="yui-b first contentbox">
      <div class="record">

        <p class="error">{translate text='nohit_prefix'} - <b>{$lookfor|escape:"html"}</b> - {translate text='nohit_suffix'}</p>

        {if $parseError}
            <p class="error">{translate text='nohit_parse_error'}</p>
        {/if}

        {if $spellingSuggestions}
        <div class="correction">{translate text='nohit_spelling'}:<br/>
        {foreach from=$spellingSuggestions item=details key=term name=termLoop}
          {$term|escape} &raquo; {foreach from=$details.suggestions item=data key=word name=suggestLoop}<a href="{$data.replace_url|escape}">{$word|escape}</a>{if $data.expand_url} <a href="{$data.expand_url|escape}"><img src="{$path}/images/silk/expand.png" alt="{translate text='spell_expand_alt'}"/></a> {/if}{if !$smarty.foreach.suggestLoop.last}, {/if}{/foreach}{if !$smarty.foreach.termLoop.last}<br/>{/if}
        {/foreach}
        </div>
        <br/>
        {/if}

        {if $filterList}
        <div class="filters">{translate text='nohit_filters'}:<br/>
        {foreach from=$filterList item=filters key=field}
        <strong>{translate text=$field}</strong>: 
          {foreach from=$filters item=filter name=valueLoop}
        {$filter.value|escape} <a href="{$filter.removalUrl|escape}"><img src="{$path}/images/silk/delete.png" alt="{translate text='Delete'}"></a>{if !$smarty.foreach.valueLoop.last}, {/if}
          {/foreach}
        <br/>
        {/foreach}
        </div>
        {/if}

      </div>
    </div>
  </div>
</div>