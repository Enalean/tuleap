{if !empty($WorldCatTerms)}
  <div class="yui-gb resulthead authorbox">
  <h3>{translate text='Subject Recommendations'}</h3>
    {foreach from=$WorldCatTerms item=section name=sectionLoop key=type}
      <div class="yui-u{if $smarty.foreach.sectionLoop.iteration == 1} first{/if}">
        <dl class="narrow_begin">
          <dt>{translate text="wcterms_`$type`"}</dt>
          {foreach from=$section item=subj name=narrowLoop}
            {if $smarty.foreach.narrowLoop.iteration == 4}
              <dd id="moreWCTerms{$type}"><a href="#" onClick="moreFacets('WCTerms{$type}'); return false;">{translate text='more'} ...</a></dd>
              </dl>
              <dl class="narrowGroupHidden" id="narrowGroupHidden_WCTerms{$type}">
            {/if}
            <dd>&bull; <a href="{$url}/Search/Results?lookfor=%22{$subj|escape:"url"}%22&type=Subject">{$subj|escape}</a></dd>
          {/foreach}
          {if $smarty.foreach.narrowLoop.total > 3}<dd><a href="#" onClick="lessFacets('WCTerms{$type}'); return false;">{translate text='less'} ...</a></dd>{/if}
        </dl>
      </div>
    {/foreach}
  </div>
{/if}
