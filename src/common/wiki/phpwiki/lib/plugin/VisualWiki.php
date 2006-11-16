<?php // -*-php-*-
rcs_id('$Id: VisualWiki.php,v 1.19 2005/10/12 06:19:31 rurban Exp $');
/*
 Copyright (C) 2002 Johannes Große (Johannes Gro&szlig;e)

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
 * Produces graphical site map of PhpWiki
 * Example for an image map creating plugin. It produces a graphical
 * sitemap of PhpWiki by calling the <code>dot</code> commandline tool
 * from graphviz (http://www.graphviz.org).
 * @author Johannes Große
 * @version 0.9
 */
define('VISUALWIKI_ALLOWOPTIONS', true);
if (!defined('VISUALWIKI_ALLOWOPTIONS'))
    define('VISUALWIKI_ALLOWOPTIONS', false);

require_once "lib/plugin/GraphViz.php";

class WikiPlugin_VisualWiki
extends WikiPlugin_GraphViz
{
    /**
     * Sets plugin type to map production
     */
    function getPluginType() {
        return ($GLOBALS['request']->getArg('debug')) ? PLUGIN_CACHED_IMG_ONDEMAND : PLUGIN_CACHED_MAP;
    }

    /**
     * Sets the plugin's name to VisualWiki. It can be called by
     * <code>&lt;?plugin VisualWiki?&gt;</code>, now. This
     * name must correspond to the filename and the class name.
     */
    function getName() {
        return "VisualWiki";
    }

    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.19 $");
    }

    /**
     * Sets textual description.
     */
    function getDescription() {
        return _("Visualizes the Wiki structure in a graph using the 'dot' commandline tool from graphviz.");
    }

    /**
     * Returns default arguments. This is put into a separate
     * function to allow its usage by both <code>getDefaultArguments</code>
     * and <code>checkArguments</code>
     */
    function defaultarguments() {
        return array('imgtype'        => 'png',
                     'width'          => false, // was 5, scale it automatically
                     'height'         => false, // was 7, scale it automatically
                     'colorby'        => 'age', // sort by 'age' or 'revtime'
                     'fillnodes'      => 'off',
                     'label'          => 'name',
                     'shape'          => 'ellipse',
                     'large_nb'       => 5,
                     'recent_nb'      => 5,
                     'refined_nb'     => 15,
                     'backlink_nb'    => 5,
                     'neighbour_list' => '',
                     'exclude_list'   => '',
                     'include_list'   => '',
                     'fontsize'       => 9,
                     'debug'          => false,
                     'help'           => false );
    }

    /**
     * Sets the default arguments. WikiPlugin also regards these as
     * the allowed arguments. Since WikiPluginCached stores an image
     * for each different set of parameters, there can be a lot of
     * these (large) graphs if you allow different parameters.
     * Set <code>VISUALWIKI_ALLOWOPTIONS</code> to <code>false</code>
     * to allow no options to be set and use only the default parameters.
     * This will need an disk space of about 20 Kbyte all the time.
     */
    function getDefaultArguments() {
        if (VISUALWIKI_ALLOWOPTIONS)
            return $this->defaultarguments();
        else
            return array();
    }

    /**
     * Substitutes each forbidden parameter value by the default value
     * defined in <code>defaultarguments</code>.
     */
    function checkArguments(&$arg) {
        extract($arg);
        $def = $this->defaultarguments();
        if (($width < 3) || ($width > 15))
            $arg['width'] = $def['width'];
        if (($height < 3) || ($height > 20))
            $arg['height'] = $def['height'];
        if (($fontsize < 8) || ($fontsize > 24))
            $arg['fontsize'] = $def['fontsize'];
        if (!in_array($label, array('name', 'number')))
            $arg['label'] = $def['label'];

        if (!in_array($shape, array('ellipse', 'box', 'point', 'circle',
                                    'plaintext')))
            $arg['shape'] = $def['shape'];
        if (!in_array($colorby, array('age', 'revtime')))
            $arg['colorby'] = $def['colorby'];
        if (!in_array($fillnodes, array('on', 'off')))
            $arg['fillnodes'] = $def['fillnodes'];
        if (($large_nb < 0) || ($large_nb > 50))
            $arg['large_nb'] = $def['large_nb'];
        if (($recent_nb < 0)  || ($recent_nb > 50))
            $arg['recent_nb'] = $def['recent_nb'];
        if (($refined_nb < 0 ) || ( $refined_nb > 50))
            $arg['refined_nb'] = $def['refined_nb'];
        if (($backlink_nb < 0) || ($backlink_nb > 50))
            $arg['backlink_nb'] = $def['backlink_nb'];
        // ToDo: check if "ImageCreateFrom$imgtype"() exists.
        if (!in_array($imgtype, $GLOBALS['PLUGIN_CACHED_IMGTYPES']))
            $arg['imgtype'] = $def['imgtype'];
        if (empty($fontname))
            $arg['fontname'] = VISUALWIKIFONT;
    }

    /**
     * Checks options, creates help page if necessary, calls both
     * database access and image map production functions.
     * @return array($map,$html)
     */
    function getMap($dbi, $argarray, $request) {
        if (!VISUALWIKI_ALLOWOPTIONS)
            $argarray = $this->defaultarguments();
        $this->checkArguments($argarray);
        $request->setArg('debug',$argarray['debug']);
        //extract($argarray);
        if ($argarray['help'])
            return array($this->helpImage(), ' '); // FIXME
        $this->createColors();
        $this->extract_wikipages($dbi, $argarray);
        /* ($dbi,  $large, $recent, $refined, $backlink,
            $neighbour, $excludelist, $includelist, $color); */
        return $this->invokeDot($argarray);
        /* => ($width, $height, $color, $shape, $text); */
	
    }

    // ------------------------------------------------------------------------------------------

    /**
     * Returns an image containing a usage description of the plugin.
     * @return string image handle
     */
    function helpImage() {
        $def = $this->defaultarguments();
        $other_imgtypes = $GLOBALS['PLUGIN_CACHED_IMGTYPES'];
        unset ($other_imgtypes[$def['imgtype']]);
        $helparr = array(
            '<?plugin '.$this->getName() .
            ' img'             => ' = "' . $def['imgtype'] . "(default)|" . join('|',$GLOBALS['PLUGIN_CACHED_IMGTYPES']).'"',
            'width'            => ' = "width in inches"',
            'height'           => ' = "height in inches"',
            'fontname'         => ' = "font family"',
            'fontsize'         => ' = "fontsize in points"',
            'colorby'          => ' = "age|revtime|none"',
            'fillnodes'        => ' = "on|off"',
            'shape'            => ' = "ellipse(default)|box|circle|point"',
            'label'            => ' = "name|number"',
            'large_nb'         => ' = "number of largest pages to be selected"',
            'recent_nb'        => ' = "number of youngest pages"',
            'refined_nb'       => ' = "#pages with smallest time between revisions"',
            'backlink_nb'      => ' = "number of pages with most backlinks"',
            'neighbour_list'   => ' = "find pages linked from and to these pages"',
            'exclude_list'     => ' = "colon separated list of pages to be excluded"',
            'include_list'     => ' = "colon separated list"     ?>'
            );
        $length = 0;
        foreach($helparr as $alignright => $alignleft) {
            $length = max($length, strlen($alignright));
        }
        $helptext ='';
        foreach($helparr as $alignright => $alignleft) {
            $helptext .= substr('                                                        '
                                . $alignright, -$length).$alignleft."\n";
        }
        return $this->text2img($helptext, 4, array(1, 0, 0),
                               array(255, 255, 255));
    }


    /**
     * Selects the first (smallest or biggest) WikiPages in
     * a given category.
     *
     * @param  number   integer  number of page names to be found
     * @param  category string   attribute of the pages which is used
     *                           to compare them
     * @param  minimum  boolean  true finds smallest, false finds biggest
     * @return array             list of page names found to be the best
     */
    function findbest($number, $category, $minimum ) {
        // select the $number best in the category '$category'
        $pages = &$this->pages;
        $names = &$this->names;

        $selected = array();
        $i = 0;
        foreach($names as $name) {
            if ($i++>=$number)
                break;
            $selected[$name] = $pages[$name][$category];
        }
        //echo "<pre>$category "; var_dump($selected); "</pre>";
        $compareto = $minimum ? 0x79999999 : -0x79999999;

        $i = 0;
        foreach ($names as $name) {
            if ($i++<$number)
                continue;
            if ($minimum) {
                if (($crit = $pages[$name][$category]) < $compareto) {
                    $selected[$name] = $crit;
                    asort($selected, SORT_NUMERIC);
                    array_pop($selected);
                    $compareto = end($selected);
                }
            } elseif (($crit = $pages[$name][$category]) > $compareto)  {
                $selected[$name] = $crit;
                arsort($selected, SORT_NUMERIC);
                array_pop($selected);
                $compareto = end($selected);
            }
        }
        //echo "<pre>$category "; var_dump($selected); "</pre>";

        return array_keys($selected);
    }


    /**
    * Extracts a subset of all pages from the wiki and find their
    * connections to other pages. Also collects some page features
    * like size, age, revision number which are used to find the
    * most attractive pages.
    *
    * @param  dbi         WikiDB   database handle to access all Wiki pages
    * @param  LARGE       integer  number of largest pages which should
    *                              be included
    * @param  RECENT      integer  number of the youngest pages to be included
    * @param  REFINED     integer  number of the pages with shortes revision
    *                              interval
    * @param  BACKLINK    integer  number of the pages with most backlinks
    * @param  EXCLUDELIST string   colon ':' separated list of page names which
    *                              should not be displayed (like PhpWiki, for
    *                              example)
    * @param  INCLUDELIST string   colon separated list of pages which are
    *                              always included (for example your own
    *                              page :)
    * @param  COLOR       string   'age', 'revtime' or 'none'; Selects which
    *                              page feature is used to determine the
    *                              filling color of the nodes in the graph.
    * @return void
    */
    function extract_wikipages($dbi, $argarray) {
        // $LARGE, $RECENT, $REFINED, $BACKLINK, $NEIGHBOUR,
        // $EXCLUDELIST, $INCLUDELIST,$COLOR
        $now = time();

        extract($argarray);
        // FIXME: gettextify?
        $exclude_list   = $exclude_list ? explode(':', $exclude_list) : array();
        $include_list   = $include_list ? explode(':', $include_list) : array();
        $neighbour_list = $neighbour_list ? explode(':', $neighbour_list) : array();

        // remove INCLUDED from EXCLUDED, includes override excludes.
        if ($exclude_list and $include_list) {
        	$diff = array_diff($exclude_list, $include_list);
        	if ($diff)
        	    $exclude_list = $diff;
        }

        // collect all pages
        $allpages = $dbi->getAllPages(false, false, false, $exclude_list);
        $pages = &$this->pages;
        $countpages = 0;
        while ($page = $allpages->next()) {
            $name = $page->getName();

            // skip excluded pages
            if (in_array($name, $exclude_list)) {
            	$page->free();	
                continue;
            }

            // false = get links from actual page
            // true  = get links to actual page ("backlinks")
            $backlinks = $page->getLinks(true);
            unset($bconnection);
            $bconnection = array();
            while ($blink = $backlinks->next()) {
                array_push($bconnection, $blink->getName());
            }
            $backlinks->free();
            unset($backlinks);

            // include all neighbours of pages listed in $NEIGHBOUR
            if (in_array($name, $neighbour_list)) {
                $ln = $page->getLinks(false);
                $con = array();
                while ($link = $ln->next()) {
                    array_push($con, $link->getName());
                }
                $include_list = array_merge($include_list, $bconnection, $con);
                $ln->free();
                unset($l);
                unset($con);
            }

            unset($rev);
            $rev = $page->getCurrentRevision();

            $pages[$name] = array(
                'age'         => $now - $rev->get('mtime'),
                'revnr'       => $rev->getVersion(),
                'links'       => array(),
                'backlink_nb' => count($bconnection),
                'backlinks'   => $bconnection,
                'size'        => 1000 // FIXME
                );
            $pages[$name]['revtime'] = $pages[$name]['age'] / ($pages[$name]['revnr']);

            unset($page);
        }
        $allpages->free();
        unset($allpages);
        $this->names = array_keys($pages);

        $countpages = count($pages);

        // now select each page matching to given parameters
        $all_selected = array_unique(array_merge(
            $this->findbest($recent_nb,   'age',         true),
            $this->findbest($refined_nb,  'revtime',     true),
            $x = $this->findbest($backlink_nb, 'backlink_nb', false),
//          $this->findbest($large_nb,    'size',        false),
            $include_list));

        foreach($all_selected as $name)
            if (isset($pages[$name]))
                $newpages[$name] = $pages[$name];
        unset($this->names);
        unset($this->pages);
        $this->pages = $newpages;
        $pages = &$this->pages;
        $this->names = array_keys($pages);
        unset($newpages);
        unset($all_selected);

        $countpages = count($pages);

        // remove dead links and collect links
        reset($pages);
        while( list($name, $page) = each($pages) ) {
            if (is_array($page['backlinks'])) {
                reset($page['backlinks']);
                while ( list($index, $link) = each( $page['backlinks'] ) ) {
                    if ( !isset($pages[$link]) || $link == $name ) {
                        unset($pages[$name]['backlinks'][$index]);
                    } else {
                        array_push($pages[$link]['links'],$name);
                        //array_push($this->everylink, array($link,$name));
                    }
                }
            }
        }

        if ($colorby == 'none')
            return;
        list($oldestname) = $this->findbest(1, $colorby, false);
        $this->oldest = $pages[$oldestname][$colorby];
        foreach($this->names as $name)
            $pages[$name]['color'] = $this->getColor($pages[$name][$colorby] / $this->oldest);
    }

    /**
     * Creates the text file description of the graph needed to invoke
     * <code>dot</code>.
     *
     * @param filename  string  name of the dot file to be created
     * @param width     float   width of the output graph in inches
     * @param height    float   height of the graph in inches
     * @param colorby   string  color sceme beeing used ('age', 'revtime',
     *                                                   'none')
     * @param shape     string  node shape; 'ellipse', 'box', 'circle', 'point'
     * @param label     string  'name': label by name,
     *                          'number': label by unique number
     * @return boolean          error status; true=ok; false=error
     */
    function createDotFile($filename, $argarray) {
        extract($argarray);
        if (!$fp = fopen($filename, 'w'))
            return false;

        $fillstring = ($fillnodes == 'on') ? 'style=filled,' : '';

        $ok = true;
        $names = &$this->names;
        $pages = &$this->pages;
        if ($names)
            $nametonumber = array_flip($names);

        $dot = "digraph VisualWiki {\n" // }
            . (!empty($fontpath) ? "    fontpath=\"$fontpath\"\n" : "");
        if ($width and $height)
            $dot .= "    size=\"$width,$height\";\n    ";


        switch ($shape) {
        case 'point':
            $dot .= "edge [arrowhead=none];\nnode [shape=$shape,fontname=$fontname,width=0.15,height=0.15,fontsize=$fontsize];\n";
            break;
        case 'box':
            $dot .= "node [shape=$shape,fontname=$fontname,width=0.4,height=0.4,fontsize=$fontsize];\n";
            break;
        case 'circle':
            $dot .= "node [shape=$shape,fontname=$fontname,width=0.25,height=0.25,fontsize=$fontsize];\n";
            break;
        default :
            $dot .= "node [fontname=$fontname,shape=$shape,fontsize=$fontsize];\n" ;
        }
        $dot .= "\n";
        $i = 0;
        foreach ($names as $name) {

            $url = rawurlencode($name);
            // patch to allow Page/SubPage
            $url = str_replace(urlencode(SUBPAGE_SEPARATOR), SUBPAGE_SEPARATOR, $url);
            $nodename = ($label != 'name' ? $nametonumber[$name] + 1 : $name);

            $dot .= "    \"$nodename\" [URL=\"$url\"";
            if ($colorby != 'none') {
                $col = $pages[$name]['color'];
                $dot .= sprintf(',%scolor="#%02X%02X%02X"', $fillstring,
                                $col[0], $col[1], $col[2]);
            }
            $dot .= "];\n";

            if (!empty($pages[$name]['links'])) {
                unset($linkarray);
                if ($label != 'name')
                    foreach($pages[$name]['links'] as $linkname)
                        $linkarray[] = $nametonumber[$linkname] + 1;
                else
                    $linkarray = $pages[$name]['links'];
                $linkstring = join('"; "', $linkarray );

                $c = count($pages[$name]['links']);
                $dot .= "        \"$nodename\" -> "
                     . ($c>1?'{':'')
                     . "\"$linkstring\";"
                     . ($c>1?'}':'')
                     . "\n";
            }
        }
        if ($colorby != 'none') {
            $dot .= "\n    subgraph cluster_legend {\n"
                 . "         node[fontname=$fontname,shape=box,width=0.4,height=0.4,fontsize=$fontsize];\n"
                 . "         fillcolor=lightgrey;\n"
                 . "         style=filled;\n"
                 . "         fontname=$fontname;\n"
                 . "         fontsize=$fontsize;\n"
                 . "         label=\"".gettext("Legend")."\";\n";
            $oldest= ceil($this->oldest / (24 * 3600));
            $max = 5;
            $legend = array();
            for($i = 0; $i < $max; $i++) {
                $time = floor($i / $max * $oldest);
                $name = '"' . $time .' '. _("days") .'"';
                $col = $this->getColor($i/$max);
                $dot .= sprintf('       %s [%scolor="#%02X%02X%02X"];',
                                $name, $fillstring, $col[0], $col[1], $col[2])
                    . "\n";
                $legend[] = $name;
            }
            $dot .= '        '. join(' -> ', $legend)
		. ";\n    }\n";
        }

        // {
        $dot .= "}\n";
        $this->source = $dot;
        // write a temp file
        $ok = fwrite($fp, $dot);
        $ok = fclose($fp) && $ok;  // close anyway

        return $ok;
    }


    /** 
     * static workaround on broken Cache or broken dot executable, 
     * called only if debug=static.
     *
     * @access private
     * @param  url      string  url pointing to the image part of the map
     * @param  map      string  &lt;area&gt; tags defining active
     *                          regions in the map
     * @param  dbi      WikiDB  database abstraction class
     * @param  argarray array   complete (!) arguments to produce 
     *                          image. It is not necessary to call 
     *                          WikiPlugin->getArgs anymore.
     * @param  request  Request ??? 
     * @return          string  html output
     */
    function embedImg($url,&$dbi,$argarray,&$request) {
        if (!VISUALWIKI_ALLOWOPTIONS)
            $argarray = $this->defaultarguments();
        $this->checkArguments($argarray);
        //extract($argarray);
        if ($argarray['help'])
            return array($this->helpImage(), ' '); // FIXME
        $this->createColors();
        $this->extract_wikipages($dbi, $argarray);
        list($imagehandle, $content['html']) = $this->invokeDot($argarray);
        // write to uploads and produce static url
        $file_dir = defined('PHPWIKI_DIR') ? 
            PHPWIKI_DIR . "/uploads" : "uploads";
        $upload_dir = SERVER_URL . ((substr(DATA_PATH,0,1)=='/') ? '' : "/") . DATA_PATH . '/uploads/';
        $tmpfile = tempnam($file_dir,"VisualWiki").".".$argarray['imgtype'];
        WikiPluginCached::writeImage($argarray['imgtype'], $imagehandle, $tmpfile);             
        ImageDestroy($imagehandle);
        return WikiPluginCached::embedMap(1,$upload_dir.basename($tmpfile),$content['html'],
                                          $dbi,$argarray,$request);
    }

    /**
     * Prepares some rainbow colors for the nodes of the graph
     * and stores them in an array which may be accessed with
     * <code>getColor</code>.
     */
    function createColors() {
        $predefcolors = array(
             array('red' => 255, 'green' =>   0, 'blue' =>   0),
             array('red' => 255, 'green' => 255, 'blue' =>   0),
             array('red' =>   0, 'green' => 255, 'blue' =>   0),
             array('red' =>   0, 'green' => 255, 'blue' => 255),
             array('red' =>   0, 'green' =>   0, 'blue' => 255),
             array('red' => 100, 'green' => 100, 'blue' => 100)
             );

        $steps = 2;
        $numberofcolors = count($predefcolors) * $steps;

        $promille = -1;
        foreach($predefcolors as $color) {
            if ($promille < 0) {
                $oldcolor = $color;
                $promille = 0;
                continue;
            }
            for ($i = 0; $i < $steps; $i++)
                $this->ColorTab[++$promille / $numberofcolors * 1000] = array(
                    floor(interpolate( $oldcolor['red'],   $color['red'],   $i/$steps )),
                    floor(interpolate( $oldcolor['green'], $color['green'], $i/$steps )),
                    floor(interpolate( $oldcolor['blue'],  $color['blue'],  $i/$steps ))
                );
            $oldcolor = $color;
        }
//echo"<pre>";  var_dump($this->ColorTab); echo "</pre>";
    }

    /**
     * Translates a value from 0.0 to 1.0 into rainbow color.
     * red -&gt; orange -&gt; green -&gt; blue -&gt; gray
     *
     * @param promille float value between 0.0 and 1.0
     * @return array(red,green,blue)
     */
    function getColor($promille) {
        foreach( $this->ColorTab as $pro => $col ) {
            if ($promille*1000 < $pro)
                return $col;
        }
        $lastcol = end($this->ColorTab);
        return $lastcol;
    }
}

/**
 * Linear interpolates a value between two point a and b
 * at a value pos.
 * @return float  interpolated value
 */
function interpolate($a, $b, $pos) {
    return $a + ($b - $a) * $pos;
}

// $Log: VisualWiki.php,v $
// Revision 1.19  2005/10/12 06:19:31  rurban
// remove INCLUDED from EXCLUDED, includes override excludes.
//
// Revision 1.18  2004/12/17 16:49:52  rurban
// avoid Invalid username message on Sign In button click
//
// Revision 1.17  2004/10/14 19:19:34  rurban
// loadsave: check if the dumped file will be accessible from outside.
// and some other minor fixes. (cvsclient native not yet ready)
//
// Revision 1.16  2004/10/12 15:34:47  rurban
// redirect stderr to display the failing msg
//
// Revision 1.15  2004/09/08 13:38:00  rurban
// improve loadfile stability by using markup=2 as default for undefined markup-style.
// use more refs for huge objects.
// fix debug=static issue in WikiPluginCached
//
// Revision 1.14  2004/09/07 13:26:31  rurban
// new WikiPluginCached option debug=static and some more sf.net defaults for VisualWiki
//
// Revision 1.13  2004/09/06 12:13:00  rurban
// provide sf.net default dotbin
//
// Revision 1.12  2004/09/06 12:08:50  rurban
// memory_limit on unix workaround
// VisualWiki: default autosize image
//
// Revision 1.11  2004/09/06 10:10:27  rurban
// fixed syntax error
//
// Revision 1.10  2004/06/19 10:06:38  rurban
// Moved lib/plugincache-config.php to config/*.ini
// use PLUGIN_CACHED_* constants instead of global $CacheParams
//
// Revision 1.9  2004/06/03 09:40:57  rurban
// WikiPluginCache improvements
//
// Revision 1.8  2004/01/26 09:18:00  rurban
// * changed stored pref representation as before.
//   the array of objects is 1) bigger and 2)
//   less portable. If we would import packed pref
//   objects and the object definition was changed, PHP would fail.
//   This doesn't happen with an simple array of non-default values.
// * use $prefs->retrieve and $prefs->store methods, where retrieve
//   understands the interim format of array of objects also.
// * simplified $prefs->get() and fixed $prefs->set()
// * added $user->_userid and class '_WikiUser' portability functions
// * fixed $user object ->_level upgrading, mostly using sessions.
//   this fixes yesterdays problems with loosing authorization level.
// * fixed WikiUserNew::checkPass to return the _level
// * fixed WikiUserNew::isSignedIn
// * added explodePageList to class PageList, support sortby arg
// * fixed UserPreferences for WikiUserNew
// * fixed WikiPlugin for empty defaults array
// * UnfoldSubpages: added pagename arg, renamed pages arg,
//   removed sort arg, support sortby arg
//
// Revision 1.7  2003/03/03 13:57:31  carstenklapp
// Added fontpath (see PhpWiki:VisualWiki), tries to be smart about which OS.
// (This plugin still doesn't work for me on OS X, but at least image files
// are actually being created now in '/tmp/cache'.)
//
// Revision 1.6  2003/01/18 22:11:45  carstenklapp
// Code cleanup:
// Reformatting & tabs to spaces;
// Added copyleft, getVersion, getDescription, rcs_id.
//

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
