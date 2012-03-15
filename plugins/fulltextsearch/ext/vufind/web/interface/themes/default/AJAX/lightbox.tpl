<![CDATA[
<div class="header" id="popupboxHeader"
     onMouseDown="this.style.cursor='move';"
     onMouseUp="this.style.cursor='default';">
  <a href="" onClick="hideLightbox(); return false;">{translate text="close"}</a>
  {$title|escape:"html"}
</div>
<div class="content" id="popupboxContent">
  {$page}
</div>
]]>