{foreach from=$reviews item=providerList key=provider}
  {foreach from=$providerList item=review}
    {if $review.Summary}
    <p>
      <img src="{$path}/images/{$review.Rating}.gif" alt="{$review.Rating}/5 Stars">
      <b>{$review.Summary}</b>, {$review.Date|date_format:"%B %e, %Y"}
    </p>
    {/if}
    {if $review.Source}
    <b>Review by {$review.Source}</b>
    {/if}
    <p class="summary">{$review.Content}</p>
    {$review.Copyright}
    {if $provider == "amazon" || $provider == "amazoneditorial"}
    <div><a target="new" href="http://amazon.com/dp/{$isbn}">{translate text="Supplied by Amazon"}</a></div>
    {/if}
    <hr/>
  {/foreach}
{foreachelse}
{translate text="No reviews were found for this record"}.
{/foreach}
