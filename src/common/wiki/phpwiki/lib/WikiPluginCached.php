<?php rcs_id('$Id: WikiPluginCached.php,v 1.19 2004/12/16 18:30:59 rurban Exp $');
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
define('PLUGIN_CACHED_MAP', 2);    	     // area maps
define('PLUGIN_CACHED_SVG', 3);    	     // special SVG/SVGZ object
define('PLUGIN_CACHED_SVG_PNG', 4);      // special SVG/SVGZ object with PNG fallback
define('PLUGIN_CACHED_SWF', 5);    	     // special SWF (flash) object
define('PLUGIN_CACHED_PDF', 6);    	     // special PDF object (inlinable?)
define('PLUGIN_CACHED_PS', 7);    	     // special PS object (inlinable?)
// boolean tests:
define('PLUGIN_CACHED_IMG_ONDEMAND', 64); // don't cache
define('PLUGIN_CACHED_STATIC', 128); 	 // make it available via /uploads/, not via /getimg.php?id=

/**
 * An extension of the WikiPlugin class to allow image output and      
 * cacheing.                                                         
 * There are several abstract functions to be overloaded. 
 * Have a look at the example files
 * <ul><li>plugin/TexToPng.php</li>
 *     <li>plugin/CacheTest.php (extremely simple example)</li>
 *     <li>plugin/RecentChangesCached.php</li>
 *     <li>plugin/Ploticus.php</li>
 * </ul>
 *
 * @author  Johannes Gro�e, Reini Urban
 */                                                                
class WikiPluginCached extends WikiPlugin
{   
    var $_static;
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
    function genUrl($cache, $argarray) {
    	global $request;
        //$cacheparams = $GLOBALS['CacheParams'];

        $plugincall = serialize( array( 
            'pluginname' => $this->getName(),
            'arguments'  => $argarray ) ); 
        $id = $cache->generateId( $plugincall );
        $plugincall_arg = rawurlencode($plugincall);
        //$plugincall_arg = md5($plugincall); // will not work if plugin has to recreate content and cache is lost

        $url = DATA_PATH . '/getimg.php?';
        if (($lastchar = substr($url,-1)) == '/') {
            $url = substr($url, 0, -1);
        }
        if (strlen($plugincall_arg) > PLUGIN_CACHED_MAXARGLEN) {
            // we can't send the data as URL so we just send the id  
            if (!$request->getSessionVar('imagecache'.$id)) {
                $request->setSessionVar('imagecache'.$id, $plugincall);
            } 
            $plugincall_arg = false; // not needed anymore
        }

        if ($lastchar == '?') {
            // this indicates that a direct call of the image creation
            // script is wished ($url is assumed to link to the script)
            $url .= "id=$id" . ($plugincall_arg ? '&args='.$plugincall_arg : '');
        } else {
            // Not yet supported.
            // We are supposed to use the indirect 404 ErrorDocument method
            // ($url is assumed to be the url of the image in 
            //  cache_dir and the image creation script is referred to in the 
            //  ErrorDocument 404 directive.)
            $url .= '/' . PLUGIN_CACHED_FILENAME_PREFIX . $id . '.img' 
                . ($plugincall_arg ? '?args='.$plugincall_arg : '');
        }
        if ($request->getArg("start_debug"))
            $url .= "&start_debug=1";
        return array($id, $url);
    } // genUrl

    function run ($dbi, $argstr, &$request, $basepage) {
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
    function getImageType(&$dbi, $argarray, &$request) {
        if (in_array($argarray['imgtype'], preg_split('/\s*:\s*/', PLUGIN_CACHED_IMGTYPES)))
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
    function embedMap($id,$url,$map,&$dbi,$argarray,&$request) {
        // id is not unique if the same map is produced twice
        $key = substr($id,0,8).substr(microtime(),0,6);
        return HTML(HTML::map(array( 'name' => $key ), $map ),
                    HTML::img( array(
                   'src'    => $url, 
                   'border' => 0,
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
    function embedImg($url, $dbi, $argarray, $request) {
        return HTML::img( array( 
            'src' => $url,
            'border' => 0,
            'alt' => htmlspecialchars($this->getAlt($dbi, $argarray, $request)) ) );
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
    function embedObject($url, $type, $args = false, $params = false) {
        if (!$args) $args = array();
        $object = HTML::object(array_merge($args, array('src' => $url, 'type' => $type)));
        if ($params)
            $object->pushContent($params);
        return $object;
    }


// --------------------------------------------------------------------------
// ---------------------- static member functions ---------------------------
// --------------------------------------------------------------------------
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
        if (function_exists('ImageTypes')) {
            $presenttypes = ImageTypes();
            foreach ($imagetypes as $imgtype => $bitmask)
                if ( $presenttypes && $bitmask )
                    array_push($supportedtypes, $imgtype);        
        } else {
            foreach ($imagetypes as $imgtype => $bitmask)
                if (function_exists("Image".$imgtype))
                    array_push($supportedtypes, $imgtype);
        }
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
        static $IMAGEHEADER = array( 
            'gif'  => 'Content-type: image/gif',
            'png'  => 'Content-type: image/png',
            'jpeg' => 'Content-type: image/jpeg',
            'xbm'  => 'Content-type: image/xbm',
            'xpm'  => 'Content-type: image/xpm',
            'gd'   => 'Content-type: image/gd',
            'gd2'  => 'Content-type: image/gd2',
            'wbmp' => 'Content-type: image/vnd.wap.wbmp', // wireless bitmaps for PDA's and such.
            'html' => 'Content-type: text/html' );
       // Todo: swf, pdf, svg, svgz
       Header($IMAGEHEADER[$doctype]);
    }


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
                // FIXME: How are values quoted? Can a value contain '"'?
                // TODO: rawurlencode(value)
            }
            return substr($argstr, 0, strlen($argstr)-1);
        }
        return '';
    } // glueArgs

    function staticUrl ($tmpfile) {
        $content['file'] = $tmpfile;
        $content['url'] = getUploadDataPath() . basename($tmpfile);
        return $content;
    }

    function tempnam($prefix = false) {
        $temp = tempnam(isWindows() ? str_replace('/', "\\", PLUGIN_CACHED_CACHE_DIR) 
                                    : PLUGIN_CACHED_CACHE_DIR,
                       $prefix ? $prefix : PLUGIN_CACHED_FILENAME_PREFIX);
        if (isWindows())
            $temp = preg_replace("/\.tmp$/", "_tmp", $temp);
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
       $imgtype = $this->decideImgType($imgtype);

       $talkedallready = ob_get_contents() || headers_sent();
       if (($imgtype=='html') || $talkedallready) {
           if (is_object($errortext))
               $errortext = $errortext->asXml();
           trigger_error($errortext, E_USER_WARNING);
       } else {
           $red = array(255,0,0);
           $grey = array(221,221,221);
           if (is_object($errortext))
               $errortext = $errortext->asString();
           $im = $this->text2img($errortext, 2, $red, $grey);
           if (!$im) { 
               trigger_error($errortext, E_USER_WARNING);
               return;
           }
           $this->writeHeader($imgtype);
           $this->writeImage($imgtype, $im); 
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

        if (function_exists('ImageFontWidth')) {
            $charx    = ImageFontWidth($fontnr);
            $chary    = ImageFontHeight($fontnr);
        } else {
            $charx = 10; $chary = 10; 
        }
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

    function newFilterThroughCmd($input, $commandLine) {
        $descriptorspec = array(
               0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
               1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
               2 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        );

        $process = proc_open("$commandLine", $descriptorspec, $pipes);
        if (is_resource($process)) {
            // $pipes now looks like this:
            // 0 => writeable handle connected to child stdin
            // 1 => readable  handle connected to child stdout
            // 2 => readable  handle connected to child stderr
            fwrite($pipes[0], $input);
            fclose($pipes[0]);
            $buf = "";
            while(!feof($pipes[1])) {
                $buf .= fgets($pipes[1], 1024);
            }
            fclose($pipes[1]);
            $stderr = '';
            while(!feof($pipes[2])) {
                $stderr .= fgets($pipes[2], 1024);
            }
            fclose($pipes[2]);
            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $return_value = proc_close($process);
            if (empty($buf)) printXML($this->error($stderr));
            return $buf;
        }
    }

    /* PHP versions < 4.3
     * TODO: via temp file looks more promising
     */
    function OldFilterThroughCmd($input, $commandLine) {
         $input = str_replace ("\\", "\\\\", $input);
         $input = str_replace ("\"", "\\\"", $input);
         $input = str_replace ("\$", "\\\$", $input);
         $input = str_replace ("`", "\`", $input);
         $input = str_replace ("'", "\'", $input);
         //$input = str_replace (";", "\;", $input);

         $pipe = popen("echo \"$input\"|$commandLine", 'r');
         if (!$pipe) {
            print "pipe failed.";
            return "";
         }
         $output = '';
         while (!feof($pipe)) {
            $output .= fread($pipe, 1024);
         }
         pclose($pipe);
         return $output;
    }

    // run "echo $source | $commandLine" and return result
    function filterThroughCmd($source, $commandLine) {
        if (check_php_version(4,3,0))
            return $this->newFilterThroughCmd($source, $commandLine);
        else 
            return $this->oldFilterThroughCmd($source, $commandLine);
    }

    /**
     * Execute system command until the outfile $until exists.
     *
     * @param  cmd   string   command to be invoked
     * @param  until string   expected output filename
     * @return       boolean  error status; true=ok; false=error
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
//

// For emacs users
// Local Variables:
// mode: php
// tab-width: 4
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>