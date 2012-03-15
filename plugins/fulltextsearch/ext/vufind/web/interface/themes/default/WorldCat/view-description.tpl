<h3>{translate text='Description'}</h3>
{assign var=marcField value=$marc->getField('520')}
<p>
  {if $marcField|getvalue:'a'}
    {$marcField|getvalue:'a'|escape}
  {else}
    {translate text='no_description'}
  {/if}
</p>
