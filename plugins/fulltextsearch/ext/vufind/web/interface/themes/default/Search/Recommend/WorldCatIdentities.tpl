{if !empty($WorldCatIdentities)}
  <div class="authorbox">
    <h3>{translate text="Authors Related to Your Search"}</h3>
    <dl class="narrow_begin">
      {foreach from=$WorldCatIdentities item=subjects name=narrowLoop key=author}
        {if $smarty.foreach.narrowLoop.iteration == 4}
          <dd id="moreWCIdents"><a href="#" onClick="moreFacets('WCIdents'); return false;">{translate text='more'} ...</a></dd>
          </dl>
          <dl class="narrowGroupHidden" id="narrowGroupHidden_WCIdents">
        {/if}
        <dd>&bull; <a href="{$url}/Search/Results?lookfor=%22{$author|escape:"url"}%22&type=Author">{$author|escape}</a>
        {if count($subjects) > 0}
          <dl class="narrow_begin" style="margin-left: 12px;">
          <dh>{translate text='Related Subjects'}:</dh>
          {foreach from=$subjects item=subj name=subjLoop}
            {if $smarty.foreach.subjLoop.iteration == 3}
              <dd id="moreWCIdents{$smarty.foreach.narrowLoop.iteration}"><a href="#" onClick="moreFacets('WCIdents{$smarty.foreach.narrowLoop.iteration}'); return false;">{translate text='more'} ...</a></dd>
              </dl>
              <dl class="narrowGroupHidden" id="narrowGroupHidden_WCIdents{$smarty.foreach.narrowLoop.iteration}" style="margin-left: 12px;">
            {/if}
            <dd>&bull; <a href="{$url}/Search/Results?lookfor=%22{$subj|escape:"url"}%22&type=Subject">{$subj|escape}</a></dd>
          {/foreach}
          {if $smarty.foreach.subjLoop.total > 2}<dd><a href="#" onClick="lessFacets('WCIdents{$smarty.foreach.narrowLoop.iteration}'); return false;">{translate text='less'} ...</a></dd>{/if}
          </dl>
        {/if}
        </dd>
      {/foreach}
      {if $smarty.foreach.narrowLoop.total > 3}<dd><a href="#" onClick="lessFacets('WCIdents'); return false;">{translate text='less'} ...</a></dd>{/if}
    </dl>
  </div>
{/if}
