<?php 
/* Download hyperapplet.jar (or hyperwiki.jar) and GraphXML.dtd from 
 *   http://hypergraph.sourceforge.net/download.html
 *   and place it into your theme directory.
 * Include this php file and adjust the width/height.
 * The static version requires a dumped "LinkDatabase.xml" via 
 *   cd themes/default; wget http://localhost/wiki/index.php/LinkDatabase?format=xml -O LinkDatabase.xml
 * into the same dir as hyperapplet.jar
 */
global $WikiTheme;
?>
<applet code="hypergraph.applications.hexplorer.HExplorerApplet.class" align="baseline" 
        archive="<?= $WikiTheme->_finddata("hyperapplet.jar") ?>"
        width="160" height="360">
<?php // the dynamic version: ?>
  <!--param name="file" value="<?= WikiURL("LinkDatabase", array('format'=>'xml')) ?>" /-->
<?php // The faster static version: dump it periodically ?>
  <param name="file" value="<?= $WikiTheme->_finddata("LinkDatabase.xml") ?>" />
  <!--param name="properties" value="<?= $WikiTheme->_finddata("hwiki.prop") ?>" /-->
  <param name="center" value="<?= $page->getName() ?>" />
</applet>