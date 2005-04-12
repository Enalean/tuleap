<?php rcs_id('$Id$');
/*
 Copyright (C) 2002 Johannes Große (Johannes Gro&szlig;e)

 This file is (not yet) part of PhpWiki.

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
 * You should set up the options in plugincache-config.php
 * $ pear install http://pear.php.net/get/Cache
 * This file belongs to WikiPluginCached.
 * @author  Johannes Große
 * @version 0.8
 */

require_once "lib/WikiPlugin.php";
require_once "lib/plugincache-config.php";
// Try the installed pear class first. It might be newer. Not yet.
// @require_once('Cache.php');
// if (!class_exists('Cache'))
require_once('Cache.php'); // We have to create your own copy here.

define('PLUGIN_CACHED_HTML',0);
define('PLUGIN_CACHED_IMG_INLINE',1);
define('PLUGIN_CACHED_IMG_ONDEMAND',2);
define('PLUGIN_CACHED_MAP',3);

/**
 * An extension of the WikiPlugin class to allow image output and      
 * cacheing.                                                         
 * There are several abstract functions to be overloaded. 
 * Have a look at the example files
 * <ul><li>plugin/TexToPng.php</li>
 *     <li>plugin/CacheTest.php (extremely simple example)</li>
 *     <li>plugin/RecentChanges.php</li>
 *     <li>plugin/VisualWiki.php</li></ul>
 *
 * @author  Johannes Große
 * @version 0.8
 */                                                                
class WikiPluginCached extends WikiPlugin
{   
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
     */
    function genUrl($cache,$argarray) {
    	global $request;
        $cacheparams = $GLOBALS['CacheParams'];

        $plugincall = serialize( array( 
            'pluginname' => $this->getName(),
            'arguments'  => $argarray ) ); 
        $id = $cache->generateId( $plugincall );

        $url = $cacheparams['cacheurl']; // FIXME: SERVER_URL ?
        if (($lastchar = substr($url,-1)) == '/') {
            $url = substr($url, 0, -1);
        }
        if (strlen($plugincall)>$cacheparams['maxarglen']) {
            // we can't send the data as URL so we just send the id  

            if (!$request->getSessionVar('imagecache'.$id)) {
                $request->setSessionVar('imagecache'.$id, $plugincall);
            } 
            $plugincall = false; // not needed anymore
        }

        if ($lastchar=='?') {
            // this indicates that a direct call of the image creation
            // script is wished ($url is assumed to link to the script)
            $url .= "id=$id" . ($plugincall ? '&args='.rawurlencode($plugincall) : '');
        } else {
            // we are supposed to use the indirect 404 ErrorDocument method
            // ($url is assumed to be the url of the image in 
            //  cache_dir and the image creation script is referred to in the 
            //  ErrorDocument 404 directive.)
            $url .= '/' . $cacheparams['filename_prefix'] . $id . '.img' 
                    . ($plugincall ? '?args='.rawurlencode($plugincall) : '');
        }
        if ($request->getArg("start_debug"))
            $url .= "&start_debug=1";
        return array($id, $url);
    } // genUrl

    /** 
     * Replaces the abstract run method of WikiPlugin to implement
     * a cache check which can avoid redundant runs. 
     * <b>Do not override this method in a subclass. Instead you may
     * rename your run method to getHtml, getImage or getMap.
     * Have a close look on the arguments and required return values,
     * however. </b>  
     * 
     * @access protected
     * @param  dbi       WikiDB  database abstraction class
     * @param  argstr    string  plugin arguments in the call from PhpWiki
     * @param  request   Request ???
     * @param  string    basepage Pagename to use to interpret links [/relative] page names.
     * @return           string  HTML output to be printed to browser
     *
     * @see #getHtml
     * @see #getImage
     * @see #getMap
     */
    function run($dbi, $argstr, &$request, $basepage) {
        $cache = WikiPluginCached::newCache();
        $cacheparams = $GLOBALS['CacheParams'];

        $sortedargs = $this->getArgs($argstr, $request);
        if (is_array($sortedargs) )
            ksort($sortedargs);

        list($id,$url) = $this->genUrl($cache, $sortedargs);

        // ---------- html and img gen. -----------------
        if ($this->getPluginType() == PLUGIN_CACHED_IMG_ONDEMAND) {
            return $this->embedImg($url, $dbi, $sortedargs, $request);
        }

        $do_save = false;
        $content = $cache->get($id, 'imagecache');
        switch($this->getPluginType()) {
            case PLUGIN_CACHED_HTML:
                if (!$content || !$content['html']) {
                    $this->resetError();
                    $content['html'] = $this->getHtml($dbi,$sortedargs,$request,$basepage);
                    if ($errortext = $this->getError()) {
                        WikiPluginCached::printError($errortext,'html');
                        return HTML();
                    }
                    $do_save = true;
                } 
                break;
            case PLUGIN_CACHED_IMG_INLINE:
                if ($cacheparams['usecache']&&(!$content || !$content['image'])) {
                    $do_save = WikiPluginCached::produceImage($content, $this, $dbi, $sortedargs, $request, 'html');
                    $content['html'] = $do_save?$this->embedImg($url, $dbi, $sortedargs, $request) : false;
                }
                break;
            case PLUGIN_CACHED_MAP:
                if (!$content || !$content['image'] || !$content['html'] ) {
                    $do_save = WikiPluginCached::produceImage($content, $this, $dbi, $sortedargs, $request, 'html');
                    $content['html'] = $do_save?WikiPluginCached::embedMap($id,
                        $url,$content['html'],$dbi,$sortedargs,$request):false;
                }
                break;
        }
        if ($do_save) {
            $expire = $this->getExpire($dbi,$sortedargs,$request);
            $cache->save($id, $content, $expire,'imagecache');
        }
        if ($content['html'])
            return $content['html'];
        return HTML();
    } // run


    /* --------------------- virtual or abstract functions ----------- */

    /**
     * Sets the type of the plugin to html, image or map 
     * prodcution
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
    function getPluginType() {
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
    function getImage($dbi,$argarray,$request) {
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
    function getExpire($dbi,$argarray,$request) {
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
    function getImageType($dbi,$argarray,$request) {
        if (in_array($argarray['imgtype'],$GLOBAL['CacheParams']['imgtypes']))
            return $argarray['imgtype'];
        else
            return 'png';
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
    function getAlt($dbi,$argarray,$request) {
        return '<?plugin '.$this->getName().' '.$this->glueArgs($argarray).'?>';
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
    function getHtml($dbi, $argarray, $request, $basepage) {
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
    function getMap($dbi, $argarray, $request) {
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
    function embedMap($id,$url,$map,$dbi,$argarray,$request) {
        // id is not unique if the same map is produced twice
        $key = substr($id,0,8).substr(microtime(),0,6);
        return HTML(HTML::map(array( 'name' => $key ), $map ),
                    HTML::img( array(
                   'src'    => $url, 
                   //  'alt'    => htmlspecialchars($this->getAlt($dbi,$argarray,$request)) 
                   'usemap' => '#'.$key ))
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
    function embedImg($url,$dbi,$argarray,$request) {
        return HTML::img( array( 
            'src' => $url,
            'alt' => htmlspecialchars($this->getAlt($dbi,$argarray,$request)) ) );         
    }


// --------------------------------------------------------------------------
// ---------------------- static member functions ---------------------------
// --------------------------------------------------------------------------

    /** 
     * Creates one static PEAR Cache object and returns copies afterwards.
     * FIXME: There should be references returned
     *
     * @access static protected
     * @return Cache  copy of the cache object
     */
    function newCache() {
        static $staticcache;
  
        $cacheparams = $GLOBALS['CacheParams'];

        if (!is_object($staticcache)) {
         /*  $pearFinder = new PearFileFinder;
            $pearFinder->includeOnce('Cache.php');*/

            $staticcache = new Cache($cacheparams['database'],$cacheparams); 
            $staticcache->gc_maxlifetime = $cacheparams['maxlifetime'];
 
            if (!$cacheparams['usecache']) {
                $staticcache->setCaching(false);
            }
        }
        return $staticcache; // FIXME: use references ?
    }

    /** 
     * Determines whether a needed image type may is available 
     * from the GD library and gives an alternative otherwise.
     *
     * @access  public static
     * @param   wish   string one of 'png', 'gif', 'jpeg', 'jpg'
     * @return         string the image type to be used ('png', 'gif', 'jpeg')
     *                        'html' in case of an error
     */

    function decideImgType($wish) {
        if ($wish=='html') return $wish;                 
        if ($wish=='jpg') { $wish = 'jpeg'; }

        $supportedtypes = array();
        // Todo: swf, pdf, ...
        $imagetypes = array(  
            'png'   => IMG_PNG,
            'gif'   => IMG_GIF,                             
            'jpeg'  => IMG_JPEG,
            'wbmp'  => IMG_WBMP,
            'xpm'   => IMG_XPM,
            /* // these do work but not with the ImageType bitmask
            'gd'    => IMG_GD,
            'gd2'   => IMG_GD,
            'xbm'   => IMG_XBM,
            */
            );

        $presenttypes = ImageTypes();
        foreach($imagetypes as $imgtype => $bitmask)
            if ( $presenttypes && $bitmask )
                array_push($supportedtypes, $imgtype);        

        if (in_array($wish, $supportedtypes)) 
            return $wish;
        elseif (!empty($supportedtypes))
            return reset($supportedtypes);
        else
            return 'html';
        
    } // decideImgType


    /** 
     * Writes an image into a file or to the browser.
     * Note that there is no check if the image can 
     * be written.
     *
     * @access  public static
     * @param   imgtype   string 'png', 'gif' or 'jpeg'
     * @param   imghandle string image handle containing the image
     * @param   imgfile   string file name of the image to be produced
     * @return  void
     * @see     decideImageType
     */
    function writeImage($imgtype, $imghandle, $imgfile=false) {
        if ($imgtype != 'html') {
            $func = "Image" . strtoupper($imgtype);    
            if ($imgfile) {
                $func($imghandle,$imgfile);
            } else {
                $func($imghandle);
            }
        }
    } // writeImage


    /** 
     * Sends HTTP Header for some predefined file types.
     * There is no parameter check.
     *
     * @access  public static
     * @param   doctype string 'gif', 'png', 'jpeg', 'html'
     * @return  void 
     */
    function writeHeader($doctype) {
        $IMAGEHEADER = array( 
            'gif'  => 'Content-type: image/gif',
            'png'  => 'Content-type: image/png',
            'jpeg' => 'Content-type: image/jpeg',
            'xbm'  => 'Content-type: image/xbm',
            'xpm'  => 'Content-type: image/xpm',
            'gd'   => 'Content-type: image/gd',
            'gd2'  => 'Content-type: image/gd2',
            'wbmp' => 'Content-type: image/vnd.wap.wbmp', // wireless bitmaps for PDA's and such.
            'html' => 'Content-type: text/html' );
        // Todo: swf
       Header($IMAGEHEADER[$doctype]);
    } // writeHeader


    /** 
     * Converts argument array to a string of format option="value". 
     * This should only be used for displaying plugin options for 
     * the quoting of arguments is not safe, yet.
     *
     * @access public static
     * @param  argarray array   contains all arguments to be converted
     * @return          string  concated arguments
     */
    function glueArgs($argarray) {
        if (!empty($argarray)) {
            $argstr = '';
            while (list($key,$value)=each($argarray)) {
                $argstr .= $key. '=' . '"' . $value . '" ';  
            // FIXME FIXME: How are values quoted? Can a value contain " ?
            }
            return substr($argstr,0,strlen($argstr)-1);
        }
        return '';
    } // glueArgs

    // ---------------------- FetchImageFromCache ------------------------------

    /** 
     * Extracts the cache entry id from the url and the plugin call
     * parameters if available.
     *
     * @access private static
     * @param  id           string   return value. Image is stored under this id.
     * @param  plugincall   string   return value. Only returned if present in url.
     *                               Contains all parameters to reconstruct
     *                               plugin call.
     * @param  cache        Cache    PEAR Cache object
     * @param  request      Request  ???
     * @param  errorformat  string   format which should be used to
     *                               output errors ('html', 'png', 'gif', 'jpeg')
     * @return boolean               false if an error occurs, true otherwise.
     *                               Param id and param plugincall are
     *                               also return values.
     */
    function checkCall1(&$id, &$plugincall,$cache,$request, $errorformat) {
        $id=$request->getArg('id');
        $plugincall=rawurldecode($request->getArg('args')); 

        if (!$id) {
           if (!$plugincall) {
                // This should never happen, so do not gettextify.
                $errortext = "Neither 'args' nor 'id' given. Cannot proceed without parameters.";
                WikiPluginCached::printError($errorformat, $errortext);
                return false;
            } else {
                $id = $cache->generateId( $plugincall );
            }
        }   
        return true;     
    } // checkCall1


    /** 
     * Extracts the parameters necessary to reconstruct the plugin
     * call needed to produce the requested image. 
     *
     * @access static private  
     * @param  plugincall string   reference to serialized array containing both 
     *                             name and parameters of the plugin call
     * @param  request    Request  ???
     * @return            boolean  false if an error occurs, true otherwise.
     *                 
     */
    function checkCall2(&$plugincall,$request) {
        // if plugincall wasn't sent by URL, it must have been
        // stored in a session var instead and we can retreive it from there
        if (!$plugincall) {
            if (!$plugincall=$request->getSessionVar('imagecache'.$id)) {
                // I think this is the only error which may occur
                // without having written bad code. So gettextify it.
                $errortext = sprintf(
                    gettext ("There is no image creation data available to id '%s'. Please reload referring page." ),
                    $id );  
                WikiPluginCached::printError($errorformat, $errortext);
                return false; 
            }       
        }
        $plugincall = unserialize($plugincall);
        return true;
    } // checkCall2


    /** 
     * Creates an image or image map depending on the plugin type. 
     * @access static private 
     * @param  content array             reference to created array which overwrite the keys
     *                                   'image', 'imagetype' and possibly 'html'
     * @param  plugin  WikiPluginCached  plugin which is called to create image or map
     * @param  dbi     WikiDB            handle to database
     * @param  argarray array            Contains all arguments needed by plugin
     * @param  request Request           ????
     * @param  errorformat string        outputs errors in 'png', 'gif', 'jpg' or 'html'
     * @return boolean                   error status; true=ok; false=error
     */
    function produceImage(&$content, $plugin, $dbi, $argarray, $request, $errorformat) {
        $plugin->resetError();
        $content['html'] = $imagehandle = false;
        if ($plugin->getPluginType() == PLUGIN_CACHED_MAP ) {
            list($imagehandle,$content['html']) = $plugin->getMap($dbi, $argarray, $request);
        } else {
            $imagehandle = $plugin->getImage($dbi, $argarray, $request);
        }

        $content['imagetype'] 
            = WikiPluginCached::decideImgType($plugin->getImageType($dbi, $argarray, $request));
        $errortext = $plugin->getError();

        if (!$imagehandle||$errortext) {
            if (!$errortext) {
                $errortext = "'<?plugin ".$plugin->getName(). ' '
                    . WikiPluginCached::glueArgs($argarray)." ?>' returned no image, " 
                    . " although no error was reported.";
            }
            WikiPluginCached::printError($errorformat, $errortext);
            return false; 
        }

        // image handle -> image data        
        $cacheparams = $GLOBALS['CacheParams'];
        $tmpfile = tempnam($cacheparams['cache_dir'],$cacheparams['filename_prefix']);
        WikiPluginCached::writeImage($content['imagetype'], $imagehandle, $tmpfile);             
        ImageDestroy($imagehandle);
        if (file_exists($tmpfile)) {
            $fp = fopen($tmpfile,'rb');
            $content['image'] = fread($fp,filesize($tmpfile));
            fclose($fp);
            unlink($tmpfile);
            if ($content['image'])
                return true;
        }
        return false;
    } // produceImage


    /** 
     * Main function for obtaining images from cache or generating on-the-fly
     * from parameters sent by url or session vars.
     *
     * @access static public
     * @param  dbi     WikiDB            handle to database
     * @param  request Request           ???
     * @param  errorformat string        outputs errors in 'png', 'gif', 'jpeg' or 'html'
     */
    function fetchImageFromCache($dbi,$request,$errorformat='png') {
        $cache   = WikiPluginCached::newCache();      
        $errorformat = WikiPluginCached::decideImgType($errorformat);

        if (!WikiPluginCached::checkCall1($id,$plugincall,$cache,$request,$errorformat)) return false;

        // check cache 
        $content = $cache->get($id,'imagecache');

        if ($content && $content['image']) {
            WikiPluginCached::writeHeader($content['imagetype']);
            print $content['image']; 
            return true;
        } 

        // produce image, now. At first, we need plugincall parameters
        if (!WikiPluginCached::checkCall2($plugincall,$request)) return false;

        $pluginname = $plugincall['pluginname'];
        $argarray   = $plugincall['arguments'];

        $loader = new WikiPluginLoader;
        $plugin = $loader->getPlugin($pluginname);

        // cache empty, but image maps have to be created _inline_
        // so ask user to reload wiki page instead
        $cacheparams = $GLOBALS['CacheParams'];
        if (($plugin->getPluginType() == PLUGIN_CACHED_MAP) && $cacheparams['force_syncmap']) {
            $errortext = gettext('Image map expired. Reload wiki page to recreate its html part.');
            WikiPluginCached::printError($errorformat, $errortext);
        }

        
        if (!WikiPluginCached::produceImage($content, $plugin, $dbi, $argarray, $request, $errorformat) ) return false;

        $expire = $plugin->getExpire($dbi,$argarray,$request);

        if ($content['image']) {
            $cache->save($id, $content, $expire,'imagecache');
            WikiPluginCached::writeHeader($content['imagetype']); 
            print $content['image'];
            return true;
        }

        $errortext = "Could not create image file from imagehandle.";
        WikiPluginCached::printError($errorformat, $errortext);
        return false; 
    } // FetchImageFromCache

    // -------------------- error handling ---------------------------- 

    /** 
     * Resets buffer containing all error messages. This is allways
     * done before invoking any abstract creation routines like
     * <code>getImage</code>.
     *
     * @access private
     * @return void
     */
    function resetError() {
        $this->_errortext = '';
    }
       
    /** 
     * Returns all accumulated error messages. 
     *
     * @access protected
     * @return string error messages printed with <code>complain</code>.
     */
    function getError() {
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
    function complain($addtext) {
        $this->_errortext .= $addtext;
    }

    /** 
     * Outputs the error as image if possible or as html text 
     * if wished or html header has already been sent.
     *
     * @access static protected
     * @param  imgtype string 'png', 'gif', 'jpeg' or 'html'
     * @param  errortext string guess what?
     * @return void
     */
    function printError($imgtype, $errortext) {
       $imgtype = WikiPluginCached::decideImgType($imgtype);

       $talkedallready = ob_get_contents() || headers_sent();
       if (($imgtype=='html') || $talkedallready) {
           trigger_error($errortext, E_USER_WARNING);
       } else {
           $red = array(255,0,0);
           $grey = array(221,221,221);
           $im = WikiPluginCached::text2img($errortext, 2, $red, $grey);
           if (!$im) { 
               trigger_error($errortext, E_USER_WARNING);
               return;
           }
           WikiPluginCached::writeHeader($imgtype);
           WikiPluginCached::writeImage($imgtype, $im); 
           ImageDestroy($im);
       }
    } // printError


    /** 
     * Basic text to image converter for error handling which allows
     * multiple line output.
     * It will only output the first 25 lines of 80 characters. Both 
     * values may be smaller if the chosen font is to big for there
     * is further restriction to 600 pixel in width and 350 in height.
     * 
     * @access static public
     * @param  txt     string  multi line text to be converted
     * @param  fontnr  integer number (1-5) telling gd which internal font to use;
     *                         I recommend font 2 for errors and 4 for help texts.
     * @param  textcol array   text color as a list of the rgb components; array(red,green,blue)
     * @param  bgcol   array   background color; array(red,green,blue)
     * @return string          image handle for gd routines
     */
    function text2img($txt,$fontnr,$textcol,$bgcol) {
        // basic (!) output for error handling

        // check parameters
        if ($fontnr<1 || $fontnr>5) {
            $fontnr = 2;
        }
        if (!is_array($textcol) || !is_array($bgcol)) {
                $textcol = array(0,0,0);
                $bgcol = array(255,255,255);
        }
        foreach( array_merge($textcol,$bgcol) as $component) {
            if ($component<0 || $component > 255) {
                $textcol = array(0,0,0);
                $bgcol = array(255,255,255);
                break;
            }
        }

        // prepare Parameters 
        
        // set maximum values
        $IMAGESIZE  = array(
            'cols'   => 80,
            'rows'   => 25,
            'width'  => 600,
            'height' => 350 );

        $charx    = ImageFontWidth($fontnr);
        $chary    = ImageFontHeight($fontnr);
        $marginx  = $charx;
        $marginy  = floor($chary/2);

        $IMAGESIZE['cols'] = min($IMAGESIZE['cols'], floor(($IMAGESIZE['width']  - 2*$marginx )/$charx));
        $IMAGESIZE['rows'] = min($IMAGESIZE['rows'], floor(($IMAGESIZE['height'] - 2*$marginy )/$chary));

        // split lines
        $y = 0;
        $wx = 0;
        do {
            $len = strlen($txt);
            $npos = strpos($txt, "\n");

            if ($npos===false) {
                $breaklen = min($IMAGESIZE['cols'],$len);
            } else {
                $breaklen = min($npos+1, $IMAGESIZE['cols']);
            }
            $lines[$y] = chop(substr($txt, 0, $breaklen));
            $wx = max($wx,strlen($lines[$y++]));
            $txt = substr($txt, $breaklen); 
        } while ($txt && ($y < $IMAGESIZE['rows']));

        // recalculate image size
        $IMAGESIZE['rows'] = $y;
        $IMAGESIZE['cols'] = $wx;
 
        $IMAGESIZE['width']  = $IMAGESIZE['cols'] * $charx + 2*$marginx;
        $IMAGESIZE['height'] = $IMAGESIZE['rows'] * $chary + 2*$marginy;

        // create blank image
        $im = @ImageCreate($IMAGESIZE['width'],$IMAGESIZE['height']);

        $col = ImageColorAllocate($im, $textcol[0], $textcol[1], $textcol[2]); 
        $bg  = ImageColorAllocate($im, $bgcol[0], $bgcol[1], $bgcol[2]); 

        ImageFilledRectangle($im, 0, 0, $IMAGESIZE['width']-1, $IMAGESIZE['height']-1, $bg);
    
        // write text lines
        foreach($lines as $nr => $textstr) {
            ImageString( $im, $fontnr, $marginx, $marginy+$nr*$chary, 
                         $textstr, $col );
        }
        return $im;
    } // text2img

} // WikiPluginCached


// For emacs users
// Local Variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>