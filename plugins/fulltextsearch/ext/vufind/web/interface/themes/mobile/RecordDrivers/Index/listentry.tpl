<li class="menu">
  <a class="noeffect" href="{$url}/Record/{$listId|escape:"url"}">
    {* TODO: improve resource icons in mobile template: *}
    <img src="{$path}/images/silk/{$listFormats.0|lower}.png">

    <span class="name">{if !$listTitle}{translate text='Title not available'}{else}{$listTitle|truncate:180:"..."|highlight:$lookfor}{/if}</span>

    <span class="arrow"></span>
  </a>
</li>
