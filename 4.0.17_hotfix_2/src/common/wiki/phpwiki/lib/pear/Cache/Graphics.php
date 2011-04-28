<?php
// +----------------------------------------------------------------------+
// | PEAR :: Cache                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Ulf Wendel <ulf.wendel@phpdoc.de>                           |
// +----------------------------------------------------------------------+
//
// $Id: Graphics.php,v 1.1 2004/06/21 08:39:38 rurban Exp $

require_once 'Cache.php';

/**
* Graphics disk cache.
* 
* The usual way to create images is to pass some arguments that describe the image 
* to a script that dynamically creates an image. For every image of a page 
* a new PHP interpreter gets started. This is a good way to kill your webserver.
* 
* When dealing with dynamically generated images you should not call another script 
* to generate the images but generate the images by the script that produces the page
* that contains the images. This is a major improvement but it's only half the way.
* 
* There's no need to rerender an image on every request. A simple disk cache can reduce
* the computation time dramatically. This is what the class graphics_cache is for. 
* 
* Usage:
* 
* // create an instance of the graphics cache
* $cache = new graphics_cache;
*
* $img = ImageCreate(...);
*
* // compute an ID for your image based on typical parameters
* $id = m5d( $size, $colors, $label);
* 
* // check if it's cached
* if (!($link = $cache->getImageLink($id, 'gif'))) {
*  
*   // hmmm, it's not cached, create it
*   ...
*   // cacheImageLink() and cacheImage() make the ImageGIF() call!
*   // cacheImage() returns the value of ImageGIF() [etc.], cacheImageLink() returns a URL
*   $link = $cache->cacheImageLink($id, $img, 'gif');
* 
* }
*
* // Ok, let's build the ImageLink
* $size = getImageSize($link[0]);
* printf('<img src="%s" %s>', $link[1], $size[3]);
*
* // for cacheImage():
* // header('Content-type: image/gif'); print $cache->cacheImage($id, $img, 'gif');
* 
*
* The class requires PHP 4.0.2+ [ImageType()]. Note that cacheImage() works with
* the output buffer. Modify it if required!
*
* @author   Ulf Wendel <ulf.wendel@phpdoc.de>
* @version  $Id: Graphics.php,v 1.1 2004/06/21 08:39:38 rurban Exp $
* @package  Cache
*/
class Cache_Graphics extends Cache {


    /**
    * Cache URL prefix.
    * 
    * Make sure that the cache URL prefix points to the $cache_dir, otherwise
    * your links will be broken. Use setCacheURL to specify the cache_url and 
    * setCacheDir() for the cache_dir.
    * 
    * @var  string
    * @see  setCacheURL(), setCacheDir()
    */
    var $cache_url = '';

    /**
    * Directory where cached files get stored.
    * s
    * Make sure that the cache_dir is writable and offers enough space. Check 
    * also if your cache_url points to the directory. Use setCacheDir() to set
    * the variable.
    * 
    * @var  string
    * @see  setCacheDir(), setCacheURL()
    */
    var $cache_dir = '';

    /**
    * Nameprefix of cached files.
    * 
    * Per default the prefix "graphics_" gets used. You might use this 
    * for versioning or to ease (manual) clean ups.
    *
    * @var      string
    */
    var $cache_file_prefix = 'graphics_';
    
    
    /**
    * Cache container group.
    *
    * @var      string
    */
    var $cache_group = 'graphics';

    
    /**
    * Mapping from supported image type to a ImageType() constant.
    * 
    * Referr to the PHP manual for more informations on ImageType()
    * 
    * @var  array
    * @link http://www.php.net/ImageType
    */
    var $imagetypes = array(
                                'gif'   => IMG_GIF, 
                                'jpg'   => IMG_JPG,
                                'png'   => IMG_PNG,
                                'wbmp'  => IMG_WBMP
                            );

                            
    /**
    * Instantiates a cache file container.
    *
    */
    function Cache_Graphics() {
    
        $this->Cache('file', array('cache_dir' => $this->cache_dir, 'filename_prefix' => $this->cache_file_prefix));
        
    } // end constructor

    
    /**
    * Returns the content of a cached image file.
    * 
    * This function can be used to send the image directly to the browser.
    * Make sure that you send a correspondending header before sending the image itself.
    *
    * Always try to get the image from the cache before you compute it. See 
    * the class docs for an example.
    *
    * @param    string  Image-ID
    * @param    string  Image type: gif, jpg, png, wbmp
    * @return   string  Image file contents if a cached file exists otherwise an empty string
    * @see      cacheImage()
    */                                    
    function getImage($id, $format = 'png') {
        $id = $this->generateID($id, $format);
        
        return $this->get($id, $this->cache_group);
    } // end func getImage

    
    /**
    * Returns an array with a link to the cached image and the image file path.
    * 
    * Always try to get the image from the cache before you compute it. See 
    * the class docs for an example.
    *
    * @param    string  Image-ID
    * @param    string  Image type: gif, jpg, png, wbmp
    * @return   array   [ full path to the image file, image url ]
    * @throw    Cache_Error
    * @see      cacheImageLink()
    */
    function getImageLink($id, $format = 'png') {
        $id = $this->generateID($id, $format);
        if (!$this->container->idExists($id, $this->cache_group)) 
            return array();

        $file = $this->cache_url . $this->cache_file_prefix . $id;

        return array($this->container->getFilename($id, $this->cache_group), $file);
    } // end func getImageLink
    

    /**
    * Create an image from the given image handler, cache it and return the file content.
    *
    * Always try to retrive the image from the cache before you compute it.
    * 
    * Warning: this function uses the output buffer. If you expect collisions 
    * modify the code.
    *
    * @param    string  Image-ID. Used as a part of the cache filename.
    *                   Use md5() to generate a "unique" ID for your image
    *                   based on characteristic values such as the color, size etc.
    * @param    string  Image handler to create the image from.
    * @param    string  Image type: gif, jpg, png, wbmp. Also used as filename suffix.
    *                   If an unsupported type is requested the functions tries to 
    *                   fallback to a supported type before throwing an exeption.
    * @return   string  Image content returned by ImageGIF/... 
    * @throws   Cache_Error
    * @access   public
    * @see      getImage()
    */
    function cacheImage($id, $img, $format = 'png') {
        if (!$id)
            return new Cache_Error('You must provide an ID for and image to be cached!', __FILE__, __LINE__);

        $id = $this->generateID($id, $format);
        $types = ImageTypes();

        // Check if the requested image type is supported by the GD lib.
        // If not, try a callback to the first available image type.
        if (!isset($this->imagetypes[$format]) || !($types & $this->imagetypes[$format])) {
            foreach ($this->imagetypes as $supported => $bitmask) {
                if ($types & $bitmask) {
                    new Cache_Error("The build in GD lib does not support the image type $format. Fallback to $supported.", __FILE__, __LINE__);
                } else {
                    return new Cache_Error("Hmm, is your PHP build with GD support? Can't find any supported types.", __FILE__, __LINE__);
                }
            }
        }

        if ($image = $this->get($id, $this->cache_group))
            return $image;

        // save the image to the output buffer, write it to disk and 
        // return the image.
        ob_end_clean();
        ob_start(); 

        if (strtoupper($format) == "JPG") {
            $genFormat = "JPEG";
        } else {
            $genFormat = strtoupper($format);
        }

        // generate the image
        $func = 'Image' . $genFormat;
        $func($img);
        ImageDestroy($img);

        ob_end();
        $image = ob_get_contents();
        ob_end_clean();

        // save the generated image to disk
        $this->save($id, $image, 0, $this->cache_group);

        return $image;
    } // end func cacheImage
    

    /**
    * Create an image from the given image handler, cache it and return a url and the file path of the image.
    *
    * Always try to retrive the image from the cache before you compute it.
    *
    * @param    string  Image-ID. Used as a part of the cache filename.
    *                   Use md5() to generate a "unique" ID for your image
    *                   based on characteristic values such as the color, size etc.
    * @param    string  Image handler to create the image from.
    * @param    string  Image type: gif, jpg, png, wbmp. Also used as filename suffix.
    *                   If an unsupported type is requested the functions tries to 
    *                   fallback to a supported type before throwing an exeption.
    * @return   array  [ full path to the image file, image url ]
    * @throws   Cache_Error
    * @access   public
    */
    function cacheImageLink($id, &$img, $format = 'png') {
        if (!$id)
            return new Cache_Error ('You must provide an ID for and image to be cached!', __FILE__, __LINE__);

        $id = $this->generateID($id, $format);
        $types = ImageTypes();

        // Check if the requested image type is supported by the GD lib.
        // If not, try a callback to the first available image type.
        if (!isset($this->imagetypes[$format]) || !($types & $this->imagetypes[$format])) {
            foreach ($this->imagetypes as $supported => $bitmask) 
                if ($types & $bitmask)
                    new Cache_Error("The build in GD lib does not support the image type $format. Fallback to $supported.", __FILE__, __LINE__);
                else
                    return new Cache_Error("Hmm, is your PHP build with GD support? Can't find any supported types.", __FILE__, __LINE__);
        }

        $url = $this->cache_url . $this->cache_file_prefix . $id;
        $ffile = $this->container->getFilename($id, $this->cache_group);

        if ($this->isCached($id, $this->cache_group) && !isExpired($id, $this->cache_group))
            return array($ffile, $url);

        if (strtoupper($format) == "JPG") {
            $genFormat = "JPEG";
        } else {
            $genFormat = strtoupper($format);
        }

        $func = 'Image' . $genFormat;
        $func($img, $ffile);

        ImageDestroy($img);

        return array($ffile, $url);
    } // end func cacheImageLink

    
    /**
    * Sets the URL prefix used when rendering HTML Tags. 
    * 
    * Make sure that the URL matches the cache directory, 
    * otherwise you'll get broken links.
    * 
    * @param    string
    * @access   public
    * @see      setCacheDir()
    */
    function setCacheURL($cache_url) {
        if ($cache_url && '/' != substr($cache_url, 1)) 
            $cache_url .= '/';
            
        $this->cache_url = $cache_url;
        
    } // end func setCacheURL

    
    /**
    * Sets the directory where to cache generated Images
    * 
    * @param    string
    * @access   public
    * @see      setCacheURL()
    */
    function setCacheDir($cache_dir) {
        if ($cache_dir && '/' != substr($cache_dir, 1))
            $cache_dir .= '/';

        $this->cache_dir = $cache_dir;
        $this->container->cache_dir = $cache_dir;
    } // end func setCacheDir
    
    
    function generateID($variable, $format = 'png') {
      return md5(serialize($variable)) . '.' . $format;
    } // end func generateID
    
    
} // end class Cache_Graphics
?>
