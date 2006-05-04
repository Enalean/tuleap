<?php // -*-php-*-
rcs_id('$Id: GraphViz.php,v 1.4 2005/05/06 16:54:59 rurban Exp $');
/*
 Copyright 2004 $ThePhpWikiProgrammingTeam

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
 * The GraphViz plugin passes all its arguments to the grapviz dot
 * binary and displays the result as cached image (PNG,GIF,SVG) or imagemap.
 *
 * @Author: Reini Urban
 *
 * Note: 
 * - We support only images supported by GD so far (PNG most likely). 
 *   EPS, PS, SWF, SVG or SVGZ and imagemaps need to be tested.
 *
 * Usage:
<?plugin GraphViz [options...]
   multiline dot script ...
?>

 * See also: VisualWiki, which also uses dot and WikiPluginCached.
 *
 * TODO: 
 * - neato binary ?
 * - expand embedded <!plugin-list pagelist !> within the digraph script.
 */

if (PHP_OS == "Darwin") { // Mac OS X
    if (!defined("GRAPHVIZ_EXE"))
        define('GRAPHVIZ_EXE', '/sw/bin/dot'); // graphviz via Fink
    // Name of the Truetypefont - at least LucidaSansRegular.ttf is always present on OS X
    if (!defined('VISUALWIKIFONT'))
        define('VISUALWIKIFONT', 'LucidaSansRegular');
    // The default font paths do not find your fonts, set the path here:
    $fontpath = "/System/Library/Frameworks/JavaVM.framework/Versions/1.3.1/Home/lib/fonts/";
    //$fontpath = "/usr/X11R6/lib/X11/fonts/TTF/";
}
elseif (isWindows()) {
    if (!defined("GRAPHVIZ_EXE"))
        define('GRAPHVIZ_EXE','dot.exe');
    if (!defined('VISUALWIKIFONT'))
        define('VISUALWIKIFONT', 'Arial');
} elseif ($_SERVER["SERVER_NAME"] == 'phpwiki.sourceforge.net') { // sf.net hack
    if (!defined("GRAPHVIZ_EXE"))
        define('GRAPHVIZ_EXE','/home/groups/p/ph/phpwiki/bin/dot');
    if (!defined('VISUALWIKIFONT'))
        define('VISUALWIKIFONT', 'luximr'); 
} else { // other os
    if (!defined("GRAPHVIZ_EXE"))
        define('GRAPHVIZ_EXE','/usr/local/bin/dot');
    // Name of the Truetypefont - Helvetica is probably easier to read
    if (!defined('VISUALWIKIFONT'))
        define('VISUALWIKIFONT', 'Helvetica');
    //define('VISUALWIKIFONT', 'Times');
    //define('VISUALWIKIFONT', 'Arial');
    // The default font paths do not find your fonts, set the path here:
    //$fontpath = "/usr/X11R6/lib/X11/fonts/TTF/";
    //$fontpath = "/usr/share/fonts/default/TrueType/";
}

require_once "lib/WikiPluginCached.php"; 

class WikiPlugin_GraphViz
extends WikiPluginCached
{

    function _mapTypes() {
    	return array("imap", "cmapx", "ismap", "cmap");
    }

    /**
     * Sets plugin type to MAP
     * or HTML if the imagetype is not supported by GD (EPS, SVG, SVGZ) (not yet)
     * or IMG_INLINE if device = png, gif or jpeg
     */
    function getPluginType() {
        $type = $this->decideImgType($this->_args['imgtype']);
        if ($type == $this->_args['imgtype'])
            return PLUGIN_CACHED_IMG_INLINE;
        $device = strtolower($this->_args['imgtype']);
    	if (in_array($device, $this->_mapTypes()))
    	    return PLUGIN_CACHED_MAP;
    	if (in_array($device, array('svg','swf','svgz','eps','ps'))) {
            switch ($this->_args['imgtype']) {
            	case 'svg':
            	case 'svgz':
                   return PLUGIN_CACHED_STATIC | PLUGIN_CACHED_SVG_PNG;
            	case 'swf':
                   return PLUGIN_CACHED_STATIC | PLUGIN_CACHED_SWF;
                default: 
                   return PLUGIN_CACHED_STATIC | PLUGIN_CACHED_HTML;
            }
        }
    	else
            return PLUGIN_CACHED_IMG_INLINE; // normal cached libgd image handles
    }
    function getName () {
        return _("GraphViz");
    }
    function getDescription () {
        return _("GraphViz image or imagemap creation of directed graphs");
    }
    function managesValidators() {
        return true;
    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.4 $");
    }
    function getDefaultArguments() {
        return array(
                     'imgtype' => 'png', // png,gif,svgz,svg,...
                     'alt'     => false,
                     'pages'   => false,  // <!plugin-list !> support
                     'exclude' => false,
                     'help'    => false,
                     );
    }
    function handle_plugin_args_cruft(&$argstr, &$args) {
        $this->source = $argstr;
    }
    /**
     * Sets the expire time to one day (so the image producing
     * functions are called seldomly) or to about two minutes
     * if a help screen is created.
     */
    function getExpire($dbi, $argarray, $request) {
        if (!empty($argarray['help']))
            return '+120'; // 2 minutes
        return sprintf('+%d', 3*86000); // approx 3 days
    }

    /**
     * Sets the imagetype according to user wishes and
     * relies on WikiPluginCached to catch illegal image
     * formats.
     * @return string 'png', 'jpeg', 'gif'
     */
    function getImageType($dbi, $argarray, $request) {
        return $argarray['imgtype'];
    }

    /**
     * This gives an alternative text description of
     * the image.
     */
    function getAlt($dbi, $argstr, $request) {
        return (!empty($this->_args['alt'])) ? $this->_args['alt']
                                             : $this->getDescription();
    }

    /**
     * Returns an image containing a usage description of the plugin.
     *
     * TODO: *map features.
     * @return string image handle
     */
    function helpImage() {
        $def = $this->defaultArguments();
        //$other_imgtypes = $GLOBALS['PLUGIN_CACHED_IMGTYPES'];
        //unset ($other_imgtypes[$def['imgtype']]);
        $imgtypes = $GLOBALS['PLUGIN_CACHED_IMGTYPES'];
        $imgtypes = array_merge($imgtypes, array("svg", "svgz", "ps"), $this->_mapTypes());
        $helparr = array(
            '<?plugin GraphViz ' .
            'imgtype'          => ' = "' . $def['imgtype'] . "(default)|" . join('|',$imgtypes).'"',
            'alt'              => ' = "alternate text"',
            'pages'            => ' = "pages,*" or <!plugin-list !> pagelist as input',
            'exclude'          => ' = "pages,*" or <!plugin-list !> pagelist as input',
            'help'             => ' bool: displays this screen',
            '...'              => ' all further lines below the first plugin line ',
            ''                 => ' and inside the tags are the dot script.',
            "\n  ?>"
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

    function createDotFile($tempfile='', $argarray=false) {
        $source =& $this->source;
        if (empty($source)) {
            // create digraph from pages
            $source = "digraph GraphViz {\n";  // }
            foreach ($argarray['pages'] as $name) { // support <!plugin-list !> pagelists
                // allow Page/SubPage
                $url = str_replace(urlencode(SUBPAGE_SEPARATOR), SUBPAGE_SEPARATOR, rawurlencode($name));
                $source .= "  \"$name\" [URL=\"$url\"];\n";
            }
            // {
            $source .= "\n  }";
        }
        /* //TODO: expand inlined plugin-list arg
         $i = 0;
         foreach ($source as $data) {
             // hash or array?
             if (is_array($data))
                 $src .= ("\t" . join(" ", $data) . "\n");
             else
                 $src .= ("\t" . '"' . $data . '" ' . $i++ . "\n");
             $src .= $source;
             $source = $src;
        }
        */
        if (!$tempfile) {
            $tempfile = $this->tempnam($this->getName().".dot");
            unlink($tempfile);
        }
        if (!$fp = fopen($tempfile, 'w'))
            return false;
        $ok = fwrite($fp, $source);
        $ok = fclose($fp) && $ok;  // close anyway
        return $ok;
    }

    function getImage($dbi, $argarray, $request) {
        if (!($dotfile = $this->createDotFile($argarray)))
            return $this->error(fmt("empty source"));

        $dotbin = GRAPHVIZ_EXE;
        $tempfiles = $this->tempnam($this->getName());
        $gif = $argarray['imgtype'];
        if (in_array($gif, array("imap", "cmapx", "ismap", "cmap"))) {
            $this->_mapfile = "$tempfiles.map";
            $gif = $this->decideImgType($argarray['imgtype']);
        }

        $ImageCreateFromFunc = "ImageCreateFrom$gif";
        $outfile = $tempfiles.".".$gif;
        $debug = $request->getArg('debug');
        if ($debug) {
            $tempdir = dirname($tempfiles);
            $tempout = $tempdir . "/.debug";
        }
        //$ok = $tempfiles;
        $this->createDotFile($tempfiles.'.dot', $argarray);
        $this->execute("$dotbin -T$gif $dotfile -o $outfile" . 
                       ($debug ? " > $tempout 2>&1" : " 2>&1"), $outfile);
        //$code = $this->filterThroughCmd($source, GRAPHVIZ_EXE . "$args");
        //if (empty($code))
        //    return $this->error(fmt("Couldn't start commandline '%s'", $commandLine));
        sleep(1);
        if (! file_exists($outfile) ) {
            $this->_errortext .= sprintf(_("%s error: outputfile '%s' not created"), 
                                         "GraphViz", $outfile);
            $this->_errortext .= ("\ncmd-line: $dotbin -T$gif $dotfile -o $outfile");
            return false;
        }
        if (function_exists($ImageCreateFromFunc))
            return $ImageCreateFromFunc( $outfile );
        return $outfile;
    }
    
    // which argument must be set to 'png', for the fallback image when svg will fail on the client.
    // type: SVG_PNG
    function pngArg() {
    	return 'imgtype';
    }
    
    function getMap($dbi, $argarray, $request) {
    	return $this->invokeDot($argarray);
        // $img = $this->getImage($dbi, $argarray, $request);
    	//return array($this->_mapfile, $img);
    }

    /**
     * Produces a dot file, calls dot twice to obtain an image and a
     * text description of active areas for hyperlinking and returns
     * an image and an html map.
     *
     * @param width     float   width of the output graph in inches
     * @param height    float   height of the graph in inches
     * @param colorby   string  color sceme beeing used ('age', 'revtime',
     *                                                   'none')
     * @param shape     string  node shape; 'ellipse', 'box', 'circle', 'point'
     * @param label     string  not used anymore
     */
    function invokeDot($argarray) {
        $dotbin = GRAPHVIZ_EXE;
        $tempfiles = $this->tempnam($this->getName());
        $gif = $argarray['imgtype'];
        $ImageCreateFromFunc = "ImageCreateFrom$gif";
        $outfile = $tempfiles.".".$gif;
        $debug = $GLOBALS['request']->getArg('debug');
        if ($debug) {
            $tempdir = dirname($tempfiles);
            $tempout = $tempdir . "/.debug";
        }
        $ok = $tempfiles
            && $this->createDotFile($tempfiles.'.dot',$argarray)
            // && $this->filterThroughCmd('',"$dotbin -T$gif $tempfiles.dot -o $outfile")
            // && $this->filterThroughCmd('',"$dotbin -Timap $tempfiles.dot -o ".$tempfiles.".map")
            && $this->execute("$dotbin -T$gif $tempfiles.dot -o $outfile" . 
                              ($debug ? " > $tempout 2>&1" : " 2>&1"), $outfile)
            && $this->execute("$dotbin -Timap $tempfiles.dot -o ".$tempfiles.".map" . 
                              ($debug ? " > $tempout 2>&1" : " 2>&1"), $tempfiles.".map")
            && file_exists( $outfile )
            && file_exists( $tempfiles.'.map' )
            && ($img = $ImageCreateFromFunc($outfile))
            && ($fp = fopen($tempfiles.'.map', 'r'));

        $map = HTML();
        if ($debug == 'static') {
            // workaround for misconfigured WikiPluginCached (sf.net) or dot.
            // present a static png and map file.
            if (file_exists($outfile) and filesize($outfile) > 900)
                $img = $outfile;
            else
                $img = $tempdir . "/".$this->getName().".".$gif;
            if (file_exists( $tempfiles.".map") and filesize($tempfiles.".map") > 20)
                $map = $tempfiles.".map";
            else
                $map = $tempdir . "/".$this->getName().".map";
            $img = $ImageCreateFromFunc($img);
            $fp = fopen($map, 'r');
            $map = HTML();	
            $ok = true;
        }
        if ($ok and $fp) {
            while (!feof($fp)) {
                $line = fgets($fp, 1000);
                if (substr($line, 0, 1) == '#')
                    continue;
                list($shape, $url, $e1, $e2, $e3, $e4) = sscanf($line,
                                                                "%s %s %d,%d %d,%d");
                if ($shape != 'rect')
                    continue;

                // dot sometimes gives not always the right order so
                // so we have to sort a bit
                $x1 = min($e1, $e3);
                $x2 = max($e1, $e3);
                $y1 = min($e2, $e4);
                $y2 = max($e2, $e4);
                $map->pushContent(HTML::area(array(
                            'shape'  => 'rect',
                            'coords' => "$x1,$y1,$x2,$y2",
                            'href'   => $url,
                            'title'  => rawurldecode($url),
                            'alt' => $url)));
            }
            fclose($fp);
            //trigger_error("url=".$url);
        } else {
            $this->_errortext = 
                ("$outfile: ".(file_exists($outfile) ? filesize($outfile):'missing')."\n".
                 "$tempfiles.map: ".(file_exists("$tempfiles.map") ? filesize("$tempfiles.map"):'missing'));
            $this->_errortext .= ("\ncmd-line: $dotbin -T$gif $tempfiles.dot -o $outfile");
            $this->_errortext .= ("\ncmd-line: $dotbin -Timap $tempfiles.dot -o ".$tempfiles.".map");
            trigger_error($this->_errortext, E_USER_WARNING);
            return array(false, false);
        }

        // clean up tempfiles
        if ($ok and !$argarray['debug'])
            foreach (array('',".$gif",'.map','.dot') as $ext) {
                if (file_exists($tempfiles.$ext))
                    unlink($tempfiles.$ext);
            }

        if ($ok)
            return array($img, $map);
        else
            return array(false, false);
    }

    /**
     * Execute system command.
     * TODO: better use invokeDot for imagemaps, linking to the pages.
     *
     * @param  cmd string   command to be invoked
     * @return     boolean  error status; true=ok; false=error
     */
    function execute($cmd, $until = false) {
        // cmd must redirect stderr to stdout though!
        $errstr = exec($cmd); //, $outarr, $returnval); // normally 127
        //$errstr = join('',$outarr);
        $ok = empty($errstr);
        if (!$ok) {
            trigger_error("\n".$cmd." failed: $errstr", E_USER_WARNING);
        } elseif ($GLOBALS['request']->getArg('debug'))
            trigger_error("\n".$cmd.": success\n", E_USER_NOTICE);
        if (!isWindows()) {
            if ($until) {
                $loop = 100000;
                while (!file_exists($until) and $loop > 0) {
                    $loop -= 100;
                    usleep(100);
                }
            } else {
                usleep(5000);
            }
        }
        if ($until)
            return file_exists($until);
        return $ok;
    }
};

// $Log: GraphViz.php,v $
// Revision 1.4  2005/05/06 16:54:59  rurban
// add failing cmdline for .map
//
// Revision 1.3  2004/12/17 16:49:52  rurban
// avoid Invalid username message on Sign In button click
//
// Revision 1.2  2004/12/14 21:34:22  rurban
// fix syntax error
//
// Revision 1.1  2004/12/13 14:45:33  rurban
// new generic GraphViz plugin: similar to Ploticus
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
