<?php // -*-php-*-
rcs_id('$Id: GoogleMaps.php,v 1.3 2005/09/18 16:11:40 rurban Exp $');
/**
 Copyright 2005 $ThePhpWikiProgrammingTeam

 This file is part of PhpWiki.

 PhpWiki is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 PhpWiki is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with PhpWiki; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

/**
 * Uses Google Maps as a Map Server
 *
 * This plugin displays a marker with further infos (when clicking) on given coordinates.

 * Hint: You need to sign up for a Google Maps API key!
 *         http://www.google.com/apis/maps/signup.html
 *       Then enter the key in config/config.ini under GOOGLE_LICENSE_KEY= 
 *
 * Usage:
 *  <?plugin GoogleMaps
 *           Latitude=53.053
 *	     Longitude=7.803
 *           ZoomFactor=10
 *           Marker=true
 *           InfoText=
 *           InfoLink=
 *           MapType=Map|Satellite|Hybrid
 *           width=500px
 *           height=400px
 *  ?>
 *
 * @author Reini Urban
 *
 * @see plugin/GooglePlugin
 *      http://www.giswiki.de/index.php/Google_Maps_Extensions
 *      http://www.google.com/apis/maps/, http://maps.google.com/
 *      http://libgmail.sourceforge.net/googlemaps.html 
 *
 * NOT YET SUPPORTED:
 *   Search for keywords (search=)
 *   mult. markers would need a new syntax
 *   directions (from - to)
 *   drawing polygons
 *   Automatic route following
 */
class WikiPlugin_GoogleMaps
extends WikiPlugin
{
    function getName() {
        return _("GoogleMaps");
    }

    function getDescription() {
      return _("Displays a marker with further infos (when clicking) on given coordinates");
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.3 $");
    }

    function getDefaultArguments() {
        return array( 
		     'Longitude' => 	'',
		     'Latitude'  => 	'',
		     'ZoomFactor'=>	5,
		     'Marker'    =>	true,
		     'InfoText'  => 	'',
		     'MapType'   =>  	'Hybrid', // Map|Satellite|Hybrid,
		     'SmallMapControl' => false,  // large or small
		     'width'     =>	'500px',
		     'height'    =>	'400px',
                    );
    }

    function run($dbi, $argstr, &$request, $basepage) {
        global $WikiTheme;

        $args = $this->getArgs($argstr, $request);
        extract($args);

        if ($Longitude === '') {
            return $this->error(fmt("%s parameter missing", "'Longitude'"));
        }
        if ($Latitude === '') {
            return $this->error(fmt("%s parameter missing", "'Latitude'"));
        }

	$maps = JavaScript('',array('src'=>"http://maps.google.com/maps?file=api&v=1&key=" . GOOGLE_LICENSE_KEY));
	$id = GenerateId("googlemap");
	switch ($MapType) {
	case "Satellite": $type = "_SATELLITE_TYPE"; break;
	case "Map":       $type = "_MAP_TYPE"; break;
	case "Hybrid":    $type = "_HYBRID_TYPE"; break;
	default: return $this->error(sprintf(_("invalid argument %s"), $MapType));
	}
	$div = HTML::div(array('id'=>$id,'style'=>'width: '.$width.'; height: '.$height));

        // TODO: Check for multiple markers or polygons
        if (!$InfoText) 
            $Marker = false;
        // Create a marker whose info window displays the given text
	if ($Marker) {
            if ($InfoText) {
                include_once("lib/BlockParser.php");
                $page = $dbi->getPage($request->getArg('pagename'));
                $rev  = $page->getCurrentRevision(false);
                $markup = $rev->get('markup');
                $markertext = TransformText($InfoText, $markup, $basepage);
            }
	    $markerjs = JavaScript("
function createMarker(point, text) {
  var marker = new GMarker(point);
  var html = text + \"<br><br><font size='-1'>[" . 
                                               _("new&nbsp;window") .
                                               "]</font>\";
  GEvent.addListener(marker, \"click\", function() {marker.openInfoWindowHtml(html);});
  return marker;
}");
	}

	$run = JavaScript("
var map = new GMap(document.getElementById('".$id."'));\n" .
($SmallMapControl 
 ? "map.addControl(new GSmallMapControl());\n"
 : "map.addControl(new GLargeMapControl());\n") . "
map.addControl(new GMapTypeControl());
map.centerAndZoom(new GPoint(".$Longitude.", ".$Latitude."), ".$ZoomFactor.");
map.setMapType(".$type.");" . 
($Marker 
 ? "
var point = new GPoint(".$Longitude.",".$Latitude.");
var marker = createMarker(point, '".$markertext->asXml()."'); map.addOverlay(marker);"
 : "")
);
        if ($Marker)
            return HTML($markerjs,$maps,$div,$run);
        else
            return HTML($maps,$div,$run);
    }
};

// $Log: GoogleMaps.php,v $
// Revision 1.3  2005/09/18 16:11:40  rurban
// fix syntax error
//
// Revision 1.2  2005/09/11 13:29:56  rurban
// remove InfoLink, wiki parse Infotext instead
//
// Revision 1.1  2005/09/07 16:53:46  rurban
// initial version, idea from http://www.giswiki.de/index.php/Google_Maps_Extensions
//
//

// (c-file-style: "gnu")
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
