<?php
/*
 Copyright (C) 2002 Johannes Große (Johannes Gro&szlig;e)
 Copyright (C) 2004 Reini Urban

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
 * You should set up the options in config/config.ini at Part seven:
 * $ pear install http://pear.php.net/get/Cache
 * This file belongs to WikiPluginCached.
 */

require_once "lib/WikiPlugin.php";
// require_once "lib/plugincache-config.php"; // replaced by config.ini settings!

// types:
define('PLUGIN_CACHED_HTML', 0);         // cached html (extensive calculation)
define('PLUGIN_CACHED_IMG_INLINE', 1);   // gd images
define('PLUGIN_CACHED_MAP', 2);             // area maps
define('PLUGIN_CACHED_SVG', 3);             // special SVG/SVGZ object
define('PLUGIN_CACHED_SVG_PNG', 4);      // special SVG/SVGZ object with PNG fallback
define('PLUGIN_CACHED_SWF', 5);             // special SWF (flash) object
define('PLUGIN_CACHED_PS', 7);             // special PS object (inlinable?)
// boolean tests:
define('PLUGIN_CACHED_IMG_ONDEMAND', 64); // don't cache
define('PLUGIN_CACHED_STATIC', 128);      // make it available via /uploads/, not via /getimg.php?id=

/**
 * An extension of the WikiPlugin class to allow image output and
 * cacheing.
 * There are several abstract functions to be overloaded.
 * Have a look at the example files
 * <ul>
 *     <li>plugin/CacheTest.php (extremely simple example)</li>
 *     <li>plugin/RecentChangesCached.php</li>
 *     <li>plugin/Ploticus.php</li>
 * </ul>
 */
class WikiPluginCached extends WikiPlugin
{
    public $_static;
    /**
     * Produces URL and id number from plugin arguments which later on,
     * will allow to find a cached image or to reconstruct the complete
     * plugin call to recreate the image.
     *
     * @param cache    object the cache object used to store the images
     * @param argarray array  all parameters (including those set to
     *                        default values) of the plugin call to be
     *                        prepared
     * @access private
     * @return array(id,url)
     *
     * TODO: check if args is needed at all (on lost cache)
     */
    public function genUrl($cache, $argarray)
    {
        global $request;
        //$cacheparams = $GLOBALS['CacheParams'];

        $plugincall = serialize(array(
            'pluginname' => $this->getName(),
            'arguments'  => $argarray ));
        $id = $cache->generateId($plugincall);
        $plugincall_arg = rawurlencode($plugincall);
        //$plugincall_arg = md5($plugincall); // will not work if plugin has to recreate content and cache is lost

        $url = DATA_PATH . '/getimg.php?';
        if (($lastchar = substr($url, -1)) == '/') {
            $url = substr($url, 0, -1);
        }
        if (strlen($plugincall_arg) > PLUGIN_CACHED_MAXARGLEN) {
            // we can't send the data as URL so we just send the id
            if (!$request->getSessionVar('imagecache' . $id)) {
                $request->setSessionVar('imagecache' . $id, $plugincall);
            }
            $plugincall_arg = false; // not needed anymore
        }

        if ($lastchar == '?') {
            // this indicates that a direct call of the image creation
            // script is wished ($url is assumed to link to the script)
            $url .= "id=$id" . ($plugincall_arg ? '&args=' . $plugincall_arg : '');
        } else {
            // Not yet supported.
            // We are supposed to use the indirect 404 ErrorDocument method
            // ($url is assumed to be the url of the image in
            //  cache_dir and the image creation script is referred to in the
            //  ErrorDocument 404 directive.)
            $url .= '/' . PLUGIN_CACHED_FILENAME_PREFIX . $id . '.img'
                . ($plugincall_arg ? '?args=' . $plugincall_arg : '');
        }
        if ($request->getArg("start_debug")) {
            $url .= "&start_debug=1";
        }
        return array($id, $url);
    } // genUrl

    public function run($dbi, $argstr, &$request, $basepage)
    {
        return HTML();
    }


    /* --------------------- virtual or abstract functions ----------- */

    /**
     * Sets the type of the plugin to html, image or map
     * production
     *
     * @access protected
     * @return int determines the plugin to produce either html,
     *             an image or an image map; uses on of the
     *             following predefined values
     *             <ul>
     *             <li>PLUGIN_CACHED_HTML</li>
     *             <li>PLUGIN_CACHED_IMG_INLINE</li>
     *             <li>PLUGIN_CACHED_IMG_ONDEMAND</li>
     *             <li>PLUGIN_CACHED_MAP</li>
     *             </ul>
     */
    public function getPluginType()
    {
        return PLUGIN_CACHED_IMG_ONDEMAND;
    }

    /**
     * Creates an image handle from the given user arguments.
     * This method is only called if the return value of
     * <code>getPluginType</code> is set to
     * PLUGIN_CACHED_IMG_INLINE or PLUGIN_CACHED_IMG_ONDEMAND.
     *
     * @access protected pure virtual
     * @param  dbi       WikiDB       database abstraction class
     * @param  argarray  array        complete (!) arguments to produce
     *                                image. It is not necessary to call
     *                                WikiPlugin->getArgs anymore.
     * @param  request   Request      ???
     * @return           imagehandle  image handle if successful
     *                                false if an error occured
     */
    public function getImage($dbi, $argarray, $request)
    {
        trigger_error('WikiPluginCached::getImage: pure virtual function in file '
                      . __FILE__ . ' line ' . __LINE__, E_USER_ERROR);
        return false;
    }

    /**
     * Sets the life time of a cache entry in seconds.
     * Expired entries are not used anymore.
     * During a garbage collection each expired entry is
     * removed. If removing all expired entries is not
     * sufficient, the expire time is ignored and removing
     * is determined by the last "touch" of the entry.
     *
     * @access protected virtual
     * @param  dbi       WikiDB       database abstraction class
     * @param  argarray  array        complete (!) arguments. It is
     *                                not necessary to call
     *                                WikiPlugin->getArgs anymore.
     * @param  request   Request      ???
     * @return           string       format: '+seconds'
     *                                '0' never expires
     */
    public function getExpire($dbi, $argarray, $request)
    {
        return '0'; // persist forever
    }

    /**
     * Decides the image type of an image output.
     * Always used unless plugin type is PLUGIN_CACHED_HTML.
     *
     * @access protected virtual
     * @param  dbi       WikiDB       database abstraction class
     * @param  argarray  array        complete (!) arguments. It is
     *                                not necessary to call
     *                                WikiPlugin->getArgs anymore.
     * @param  request   Request      ???
     * @return           string       'png', 'jpeg' or 'gif'
     */
    public function getImageType(&$dbi, $argarray, &$request)
    {
        if (in_array($argarray['imgtype'], preg_split('/\s*:\s*/', PLUGIN_CACHED_IMGTYPES))) {
            return $argarray['imgtype'];
        } else {
            return 'png';
        }
    }

    /**
     * Produces the alt text for an image.
     * <code> &lt;img src=... alt="getAlt(...)"&gt; </code>
     *
     * @access protected virtual
     * @param  dbi       WikiDB       database abstraction class
     * @param  argarray  array        complete (!) arguments. It is
     *                                not necessary to call
     *                                WikiPlugin->getArgs anymore.
     * @param  request   Request      ???
     * @return           string       "alt" description of the image
     */
    public function getAlt($dbi, $argarray, $request)
    {
        return '<?plugin ' . $this->getName() . ' ' . $this->glueArgs($argarray) . '?>';
    }

    /**
     * Creates HTML output to be cached.
     * This method is only called if the plugin_type is set to
     * PLUGIN_CACHED_HTML.
     *
     * @access protected pure virtual
     * @param  dbi       WikiDB       database abstraction class
     * @param  argarray  array        complete (!) arguments to produce
     *                                image. It is not necessary to call
     *                                WikiPlugin->getArgs anymore.
     * @param  request   Request      ???
     * @param  string    $basepage    Pagename to use to interpret links [/relative] page names.
     * @return           string       html to be printed in place of the plugin command
     *                                false if an error occured
     */
    public function getHtml($dbi, $argarray, $request, $basepage)
    {
        trigger_error('WikiPluginCached::getHtml: pure virtual function in file '
                      . __FILE__ . ' line ' . __LINE__, E_USER_ERROR);
    }

    /**
     * Creates HTML output to be cached.
     * This method is only called if the plugin_type is set to
     * PLUGIN_CACHED_HTML.
     *
     * @access protected pure virtual
     * @param  dbi       WikiDB       database abstraction class
     * @param  argarray  array        complete (!) arguments to produce
     *                                image. It is not necessary to call
     *                                WikiPlugin->getArgs anymore.
     * @param  request   Request      ???
     * @return array(html,handle)     html for the map interior (to be specific,
     *                                only &lt;area;&gt; tags defining hot spots)
     *                                handle is an imagehandle to the corresponding
     *                                image.
     *                                array(false,false) if an error occured
     */
    public function getMap($dbi, $argarray, $request)
    {
        trigger_error('WikiPluginCached::getHtml: pure virtual function in file '
                      . __FILE__ . ' line ' . __LINE__, E_USER_ERROR);
    }

    /* --------------------- produce Html ----------------------------- */

    /**
     * Creates an HTML map hyperlinked to the image specified
     * by url and containing the hotspots given by map.
     *
     * @access private
     * @param  id       string  unique id for the plugin call
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
    public function embedMap($id, $url, $map, &$dbi, $argarray, &$request)
    {
        // id is not unique if the same map is produced twice
        $key = substr($id, 0, 8) . substr(microtime(), 0, 6);
        return HTML(
            HTML::map(array( 'name' => $key ), $map),
            HTML::img(array(
                   'src'    => $url,
                   'border' => 0,
                   //  'alt'    => htmlspecialchars($this->getAlt($dbi,$argarray,$request))
                   'usemap' => '#' . $key ))
        );
    }

    /**
     * Creates an HTML &lt;img&gt; tag hyperlinking to the specified
     * url and produces an alternative text for non-graphical
     * browsers.
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
    public function embedImg($url, $dbi, $argarray, $request)
    {
        return HTML::img(array(
            'src' => $url,
            'border' => 0,
            'alt' => htmlspecialchars($this->getAlt($dbi, $argarray, $request)) ));
    }

    /**
     * svg?, swf, ...
     <object type="audio/x-wav" standby="Loading Audio" data="example.wav">
       <param name="src" value="example.wav" valuetype="data"></param>
       <param name="autostart" value="false" valuetype="data"></param>
       <param name="controls" value="ControlPanel" valuetype="data"></param>
       <a href="example.wav">Example Audio File</a>
     </object>
     * See http://www.protocol7.com/svg-wiki/?EmbedingSvgInHTML
     <object data="sample.svgz" type="image/svg+xml"
             width="400" height="300">
       <embed src="sample.svgz" type="image/svg+xml"
              width="400" height="300" />
       <p>Alternate Content like <img src="" /></p>
     </object>
     */
    // how to handle alternate images? always provide alternate static images?
    public function embedObject($url, $type, $args = false, $params = false)
    {
        if (!$args) {
            $args = array();
        }
        $object = HTML::object(array_merge($args, array('src' => $url, 'type' => $type)));
        if ($params) {
            $object->pushContent($params);
        }
        return $object;
    }


// --------------------------------------------------------------------------
// ---------------------- static member functions ---------------------------
// --------------------------------------------------------------------------


    /**
     * Converts argument array to a string of format option="value".
     * This should only be used for displaying plugin options for
     * the quoting of arguments is not safe, yet.
     *
     * @access public static
     * @param  argarray array   contains all arguments to be converted
     * @return          string  concated arguments
     */
    public function glueArgs($argarray)
    {
        if (!empty($argarray)) {
            $argstr = '';
            foreach ($argarray as $key => $value) {
                $argstr .= $key . '=' . '"' . $value . '" ';
                // FIXME: How are values quoted? Can a value contain '"'?
                // TODO: rawurlencode(value)
            }
            return substr($argstr, 0, strlen($argstr) - 1);
        }
        return '';
    } // glueArgs

    public function staticUrl($tmpfile)
    {
        $content['file'] = $tmpfile;
        $content['url'] = getUploadDataPath() . basename($tmpfile);
        return $content;
    }

    public function tempnam($prefix = false)
    {
        $temp = tempnam(
            isWindows() ? str_replace('/', "\\", PLUGIN_CACHED_CACHE_DIR)
                                    : PLUGIN_CACHED_CACHE_DIR,
            $prefix ? $prefix : PLUGIN_CACHED_FILENAME_PREFIX
        );
        if (isWindows()) {
            $temp = preg_replace("/\.tmp$/", "_tmp", $temp);
        }
        return $temp;
    }

    // -------------------- error handling ----------------------------

    /**
     * Resets buffer containing all error messages. This is allways
     * done before invoking any abstract creation routines like
     * <code>getImage</code>.
     *
     * @access private
     * @return void
     */
    public function resetError()
    {
        $this->_errortext = '';
    }

    /**
     * Returns all accumulated error messages.
     *
     * @access protected
     * @return string error messages printed with <code>complain</code>.
     */
    public function getError()
    {
        return $this->_errortext;
    }

    /**
     * Collects the error messages in a string for later output
     * by WikiPluginCached. This should be used for any errors
     * that occur during data (html,image,map) creation.
     *
     * @access protected
     * @param  addtext string errormessage to be printed (separate
     *                        multiple lines with '\n')
     * @return void
     */
    public function complain($addtext)
    {
        $this->_errortext .= $addtext;
    }
} // WikiPluginCached


// $Log: WikiPluginCached.php,v $
// Revision 1.20  2005/09/26 06:28:46  rurban
// beautify tempnam() on Windows. Move execute() from above here
//
// Revision 1.19  2004/12/16 18:30:59  rurban
// avoid ugly img border
//
// Revision 1.18  2004/11/01 10:43:57  rurban
// seperate PassUser methods into seperate dir (memory usage)
// fix WikiUser (old) overlarge data session
// remove wikidb arg from various page class methods, use global ->_dbi instead
// ...
//
// Revision 1.17  2004/10/12 15:06:02  rurban
// fixes for older php, removed warnings
//
// Revision 1.16  2004/10/12 14:56:57  rurban
// lib/WikiPluginCached.php:731: Notice[8]: Undefined property: _static
//
// Revision 1.15  2004/09/26 17:09:23  rurban
// add SVG support for Ploticus (and hopefully all WikiPluginCached types)
// SWF not yet.
//
// Revision 1.14  2004/09/25 16:26:08  rurban
// some plugins use HTML
//
// Revision 1.13  2004/09/22 13:46:25  rurban
// centralize upload paths.
// major WikiPluginCached feature enhancement:
//   support _STATIC pages in uploads/ instead of dynamic getimg.php? subrequests.
//   mainly for debugging, cache problems and action=pdf
//
// Revision 1.12  2004/09/07 13:26:31  rurban
// new WikiPluginCached option debug=static and some more sf.net defaults for VisualWiki
//
// Revision 1.11  2004/09/06 09:12:46  rurban
// improve pear handling with silent fallback to ours
// For emacs users
// Local Variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
