<li class="menu">
  <a class="noeffect" href="{$url}/Record/{$summId|escape:"url"}">
    {* TODO: improve resource icons in mobile template: *}
    <img src="{$path}/images/silk/{$summFormats.0|lower}.png">

    <span class="name">{if !$summTitle}{translate text='Title not available'}{else}{$summTitle|truncate:180:"..."|highlight:$lookfor}{/if}</span>

    <span class="arrow"></span>
  </a>
</li>
