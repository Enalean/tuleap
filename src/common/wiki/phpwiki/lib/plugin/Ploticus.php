<?php // -*-php-*-
rcs_id('$Id: Ploticus.php,v 1.12 2004/12/13 14:37:22 rurban Exp $');
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
 * The Ploticus plugin passes all its arguments to the ploticus 
 * binary and displays the result as PNG, GIF, EPS, SVG or SWF.
 * Ploticus is a free, GPL, non-interactive software package 
 * for producing plots, charts, and graphics from data.
 * See http://ploticus.sourceforge.net/doc/welcome.html
 *
 * @Author: Reini Urban
 *
 * Note: 
 * - For windows you need either a gd library with GIF support or 
 *   a ploticus with PNG support. This comes e.g. with the cygwin build.
 * - We support only images supported by GD so far (PNG most likely). 
 *   No EPS, PS, SWF, SVG or SVGZ support yet, due to limitations in WikiPluginCached.
 *   This will be fixed soon.
 *
 * Usage:
<?plugin Ploticus device=png [ploticus options...]
   multiline ploticus script ...
?>
 * or without any script: (not tested)
<?plugin Ploticus -prefab vbars data=myfile.dat delim=tab y=1 clickmapurl="http://mywiki.url/wiki/?pagename=@2" clickmaplabel="@3" -csmap ?>
 *
 * TODO: PloticusSql - create intermediate data from SQL. Similar to SqlResult, just in graphic form.
 * For example to produce nice looking pagehit statistics or ratings statistics.
 * Ploticus has its own sql support within #getproc data, but this would expose security information.
 */

if (!defined("PLOTICUS_EXE"))
  if (isWindows())
    define('PLOTICUS_EXE','pl.exe');
  else
    define('PLOTICUS_EXE','/usr/local/bin/pl');
//TODO: check $_ENV['PLOTICUS_PREFABS'] and default directory

require_once "lib/WikiPluginCached.php"; 

class WikiPlugin_Ploticus
extends WikiPluginCached
{
    /**
     * Sets plugin type to MAP if -csmap (-map or -mapdemo or -csmapdemo not supported)
     * or HTML if the imagetype is not supported by GD (EPS, SVG, SVGZ) (not yet)
     * or IMG_INLINE if device = png, gif or jpeg
     */
    function getPluginType() {
    	if (!empty($this->_args['-csmap']))
    	    return PLUGIN_CACHED_MAP; // not yet tested
        // produce these on-demand so far, uncached. 
        // will get better support in WikiPluginCached soon.
        // FIXME: html also? what about ''?
        $type = $this->decideImgType($this->_args['device']);
        if ($type == $this->_args['device'])
            return PLUGIN_CACHED_IMG_INLINE;
        $device = strtolower($this->_args['device']);
    	if (in_array($device, array('svg','swf','svgz','eps','ps','pdf','html'))) {
            switch ($this->_args['device']) {
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
        return _("Ploticus");
    }
    function getDescription () {
        return _("Ploticus image creation");
    }
    function managesValidators() {
        return true;
    }
    function getVersion() {
        return preg_replace("/[Revision: $]/", '',
                            "\$Revision: 1.12 $");
    }
    function getDefaultArguments() {
        return array(
                     'device' => 'png', // png,gif,svgz,svg,...
                     '-prefab' => '',
                     '-csmap' => false,
                     'data'    => false, // <!plugin-list !> support
                     'alt'    => false,
                     'help'   => false,
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
     * (I feel unsure whether this option is reasonable in
     *  this case, because png will definitely have the
     *  best results.)
     *
     * @return string 'png', 'jpeg', 'gif'
     */
    function getImageType($dbi, $argarray, $request) {
        return $argarray['device'];
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
     * TODO: -csmap pointing to the Ploticus documentation at sf.net.
     * @return string image handle
     */
    function helpImage() {
        $def = $this->defaultArguments();
        //$other_imgtypes = $GLOBALS['PLUGIN_CACHED_IMGTYPES'];
        //unset ($other_imgtypes[$def['imgtype']]);
        $helparr = array(
            '<?plugin Ploticus ' .
            'device'           => ' = "' . $def['device'] . "(default)|" 
                                  . join('|',$GLOBALS['PLUGIN_CACHED_IMGTYPES']).'"',
            'data'             => ' <!plugin-list !>: pagelist as input',
            'alt'              => ' = "alternate text"',
            '-csmap'           => ' bool: clickable map?',
            'help'             => ' bool: displays this screen',
            '...'              => ' all further lines below the first plugin line ',
            ''                 => ' and inside the tags are the ploticus script.',
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

    function withShellCommand($script) {
        $findme  = 'shell';
        $pos = strpos($script, $findme); // uppercase?
        if ($pos === false) 
            return 0;
        return 1;
    }

    function getImage($dbi, $argarray, $request) {
        //extract($this->getArgs($argstr, $request));
        //extract($argarray);
        $source =& $this->source;
        if (!empty($source)) {
            if ($this->withShellCommand($source)) {
                $this->_errortext .= _("shell commands not allowed in Ploticus");
                return false;
            }
            if (is_array($argarray['data'])) { // support <!plugin-list !> pagelists
                $src = "#proc getdata\ndata:";
                $i = 0;
                foreach ($argarray['data'] as $data) {
                    // hash or array?
                    if (is_array($data))
                        $src .= ("\t" . join(" ", $data) . "\n");
                    else
                        $src .= ("\t" . '"' . $data . '" ' . $i++ . "\n");
                }
                $src .= $source;
                $source = $src;
            }
            $tempfile = $this->tempnam('Ploticus');
            unlink($tempfile);
            $gif = $argarray['device'];
            $args = " -stdin -$gif -o $tempfile.$gif";
            if (!empty($argarray['-csmap'])) {
            	$args .= " -csmap -mapfile $tempfile.map";
            	$this->_mapfile = "$tempfile.map";
            }
            if (!empty($argarray['-prefab'])) {
            	//check $_ENV['PLOTICUS_PREFABS'] and default directory
                global $HTTP_ENV_VARS;
                if (empty($HTTP_ENV_VARS['PLOTICUS_PREFABS'])) {
                    if (file_exists("/usr/share/ploticus"))
                        $HTTP_ENV_VARS['PLOTICUS_PREFABS'] = "/usr/share/ploticus";
                }
            	$args .= (" -prefab " . $argarray['-prefab']);
            }
            $code = $this->filterThroughCmd($source, PLOTICUS_EXE . "$args");
            //if (empty($code))
            //    return $this->error(fmt("Couldn't start commandline '%s'", $commandLine));
            sleep(1);
            if (! file_exists("$tempfile.$gif") ) {
                $this->_errortext .= sprintf(_("%s error: outputfile '%s' not created"), 
                                             "Ploticus", "$tempfile.$gif");
                $this->_errortext .= ("\ncmd-line: cat script | " . PLOTICUS_EXE . "$args");
                return false;
            }
            $ImageCreateFromFunc = "ImageCreateFrom$gif";
            if (function_exists($ImageCreateFromFunc))
                return $ImageCreateFromFunc( "$tempfile.$gif" );
            return "$tempfile.$gif";
        } else {
            return $this->error(fmt("empty source"));
        }
    }
    
    // which argument must be set to 'png', for the fallback image when svg will fail on the client.
    // type: SVG_PNG
    function pngArg() {
    	return 'device';
    }
    
    function getMap($dbi, $argarray, $request) {
    	$img = $this->getImage($dbi, $argarray, $request);
    	return array($this->_mapfile, $img);
    }
};

// $Log: Ploticus.php,v $
// Revision 1.12  2004/12/13 14:37:22  rurban
// simplify msg for new GraphViz plugin
//
// Revision 1.11  2004/10/14 13:48:07  rurban
// wait for fs and print failing cmdline
//
// Revision 1.10  2004/10/04 23:43:35  rurban
// honor _ENV PLOTICUS_PREFABS
//
// Revision 1.9  2004/09/26 17:09:23  rurban
// add SVG support for Ploticus (and hopefully all WikiPluginCached types)
// SWF not yet.
//
// Revision 1.8  2004/09/22 15:23:56  rurban
// support <!plugin-list !> pagelist data
// add -prefab arg support
//
// Revision 1.7  2004/09/22 13:46:26  rurban
// centralize upload paths.
// major WikiPluginCached feature enhancement:
//   support _STATIC pages in uploads/ instead of dynamic getimg.php? subrequests.
//   mainly for debugging, cache problems and action=pdf
//
// Revision 1.6  2004/09/07 13:26:31  rurban
// new WikiPluginCached option debug=static and some more sf.net defaults for VisualWiki
//
// Revision 1.5  2004/06/28 16:35:12  rurban
// prevent from shell commands
//
// Revision 1.4  2004/06/19 10:06:38  rurban
// Moved lib/plugincache-config.php to config/*.ini
// use PLUGIN_CACHED_* constants instead of global $CacheParams
//
// Revision 1.3  2004/06/03 09:40:57  rurban
// WikiPluginCache improvements
//
// Revision 1.2  2004/06/02 19:37:07  rurban
// extended description
//
// Revision 1.1  2004/06/02 19:12:42  rurban
// new Ploticus plugin
//
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
