{foreach from=$recordFormat item=format}
%0 {$format}
{/foreach}
{assign var=marcField value=$marc->getField('100')}
{if $marcField}
%A {$marcField|getvalue:'a'}
{/if}
{assign var=marcField value=$marc->getFields('260')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
%C {$field|getvalue:'b'|replace:',':''}
%D {$field|getvalue:'c'|replace:'.':''|replace:'[':''|replace:']':''}
%I {$field|getvalue:'a'|replace:':':''|replace:'[':''|replace:']':''}
{/foreach}
{/if}
{assign var=marcField value=$marc->getFields('700')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
%E {$field|getvalue:'a'}
{/foreach}
{/if}
{foreach from=$recordLanguage item=lang}
%G {$lang}
{/foreach}
{* Load the three possible subject fields -- 440 is deprecated but
   still exists in many catalogs. *}
{assign var=marcField440 value=$marc->getFields('440')}
{assign var=marcField490 value=$marc->getFields('490')}
{assign var=marcField830 value=$marc->getFields('830')}
{* Check for 490's with indicator 1 == 0; these should be displayed
   since they will have no corresponding 830 field.  Other 490s would
   most likely be redundant and can be ignored. *}
{assign var=visible490 value=0}
{if $marcField490}
{foreach from=$marcField490 item=field}
{if $field->getIndicator(1) == 0}
{assign var=visible490 value=1}
{/if}
{/foreach}
{/if}
{* Display subject section if at least one subject exists. *}
{if $marcField440 || $visible490 || $marcField830}
{if $marcField440}
{foreach from=$marcField440 item=field name=loop}
%B {$field|getvalue:'a'}
{/foreach}
{/if}
{if $visible490}
{foreach from=$marcField490 item=field name=loop}
{if $field->getIndicator(1) == 0}
%B {$field|getvalue:'a'}
{/if}
{/foreach}
{/if}
{if $marcField830}
{foreach from=$marcField830 item=field name=loop}
%B {$field|getvalue:'a'}
{/foreach}
{/if}
{/if}
{* ISBN: *}
{assign var=marcField value=$marc->getFields('020')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
%@ {$field|getvalue:'a'}
{/foreach}
{/if}
{assign var=marcField value=$marc->getFields('022')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
%@ {$field|getvalue:'a'}
{/foreach}
{/if}
{assign var=marcField value=$marc->getField('245')}
%T {$marcField|getvalue:'a'}{if $marcField|getvalue:'b'} {$marcField|getvalue:'b'|replace:'/':''}{/if}

{assign var=marcField value=$marc->getFields('856')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
%U {$field|getvalue:'u'}
{/foreach}
{/if}
{assign var=marcField value=$marc->getFields('250')}
{if $marcField}
{foreach from=$marcField item=field name=loop}
%7 {$field|getvalue:'a'}
{/foreach}
{/if}