<?php
/* Download hyperwiki.jar and GraphXML.dtd from
   *   http://hypergraph.sourceforge.net/download.html
   *   and place it into your theme directory.
   * Include this php file and adjust the width/height.
   */
global $WikiTheme;
  // via the RPC interface it goes like this...
?>
<applet code="hypergraph.applications.hwiki.HWikiApplet.class" 
        archive="<?php echo $WikiTheme->_finddata("hyperwiki.jar") ?>" 
        width="162" height="240">
  <param name="startPage" value="<?php echo $page->getName() ?>" />
  <param name="properties" value="<?php echo $WikiTheme->_finddata("hwiki.prop") ?>" />
  <param name="wikiURL" value="<?php echo SCRIPT_NAME ?>" />
</applet>
