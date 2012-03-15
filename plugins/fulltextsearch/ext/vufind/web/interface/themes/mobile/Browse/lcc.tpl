<span class="graytitle">Browse Call Numbers</span>
<ul class="pageitem">
  {foreach from=$defaultList item=area key=letter}
  <li class="menu"><a href="{$url}/Browse/LCC"><span class="name">{$letter} - {$area}</span><span class="arrow"></span></a></li>
  {/foreach}
</ul>
