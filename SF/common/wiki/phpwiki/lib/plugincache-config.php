<?php rcs_id('$Id: plugincache-config.php 1422 2005-04-12 13:33:49Z guerin $');
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
 * Configuration file for the cache usage of WikiPluginCached
 * Parameters for the cache configuration of WikiPluginCached and
 * all plugins derived from this class.
 * Esp. check $CacheParams['cache_dir'] and $CacheParams['cacheurl']
 *
 * @author  Johannes Große
 * @version 0.8
 */ 

/**
 * translate kilobyte and megabyte into bytes and day into seconds
 */
define('Kilobyte', 1024);
define('Megabyte', 1024*1024);
define('Day', 24 * 60 * 60 );

global $CacheParams;

/**
 * Contains the global cache configuration settings.
 * 
 * @param 'database'        string Chooses the database used by the Pear
 *                          Cache class. Should always be set to 'file' 
 *                          for this is the fastest.
 * @param 'cache_dir'       string path to use for a file cache. 
 *                          This is only used if database is set to file.
 * @param 'filename_prefix' string Every file name in the cache
 *                          begins with this prefix. 
 * @param 'highwater'       int the maximum total space in bytes of
 *                          all files in the cache. When highwater is 
 *                          exceeded, a garbage collection will start.
 *                          It will collect garbage till 'lowwater' is 
 *                          reached. 
 * @param 'lowwater'        int Stop garbage collection as soon as less than
 *                          'lowwater' bytes are used by the cache.
 * @param 'maxlifetime'     int time in seconds a cache object is allowed
 *                          not to be touched without beeing garbage collected
 * @param 'cache_url'       string URL used to call an image from the
 *                          cache. By default this is 
 *                          'url/to/wiki/getimg.php?' where 'getimg.php'
 *                          replaces 'index.php' or 'wiki.php', respectively. 
 *                          If you do not have a 'getimg.php' you can 
 *                          create it by copying your 'wiki.php'
 *                          ('index.php') to 'getimg.php' and replace
 *                          <code>include "lib/main.php"; </code>
 *                          (to be found at the end of the file) by 
 *                          <code>include "lib/imagecache.php"; </code>. 
 *                          <b>Make sure that there is a trailing
 *                          question mark in 'cache_url'.</b>
 * @param 'maxarglen'       int number of characters allowed to be send as 
 *                          parameters in the url before using sessions
 *                          vars instead. 
 * @param 'usecache'        boolean switches the use of the cache on (true)
 *                          or off (false). If you want to avoid the
 *                          usage of a cache but need WikiPlugin~s
 *                          that nevertheless rely on a cache you might set
 *                          'usecache' to false. You still need to set
 *                          'cache_dir' appropriately to allow image creation
 *                          and you should set 'force_syncmap' to false.
 * @param 'force_syncmap'   boolean Will prevent image creation for an image 
 *                          map 'on demand'. It is a good idea to set this
 *                          to 'true' because it will also prevent the 
 *                          html part not to fit to the image of the map.
 *                          If you don't use a cache, you have to set it
 *                          to 'false', maps will not work otherwise but
 *                          strange  effects may happen if the output of
 *                          an image map producing WikiPlugin is not 
 *                          completely determined by its parameters.
 *                          (As it is the case for a graphical site map.)
 */   
$CacheParams = array(
    // db settings (database='file' is the fastest)
        'database'        => 'file',
    // the webserver muist have write access to this dir!
        'cache_dir'       => (substr(PHP_OS,0,3) == 'WIN') 
	                       ? ($GLOBALS['HTTP_ENV_VARS']['TEMP'] . "\\cache\\")
	                       : '/tmp/cache/',
        'filename_prefix' => 'phpwiki',

    // When highwater is exceeded, a garbage collection will start. 
    // It will collect garbage till lowwater is reached.
        'highwater'       => 4 * Megabyte,
        'lowwater'        => 3 * Megabyte,

    // If an image has not been used for maxlifetime remove it from
    // the cache.
    // (Since there is also the highwater/lowwater mechanism
    //  and an image usually requires only 1kb you don't have to
    //  make it very small, I think.)
        'maxlifetime'     => 30 * Day,

    // name of the imagecache start up file
    // This file should have been created by hand by copying
    // phpwiki/index.php and substituting 
    //    include "lib/main.php";
    // by 
    //    include "lib/imagecache.php";
    //
    //'cacheurl'        => '../imagecache/',
        'cacheurl'        => DATA_PATH . '/getimg.php?',

    // usually send plugin arguments as URL, but when they become
    // longer than maxarglen store them in session variables    
    // setting it to 3000 worked fine for me, 30000 completely
    // crashed my linux, 1000 should be safe.
        'maxarglen'       => 1000,

    // actually use the cache 
    // (should be always true unless you are debugging)
        'usecache'        => true,

    //   This will prevent image creation for an image 
    // map 'on demand'. It is a good idea to set this
    // to 'true' because it will also prevent the 
    // html part not to fit to the image of the map.
    //   If you don't use a cache, you have to set it
    // to 'false', maps will not work otherwise.
        'force_syncmap'   => true,

    // if ImageTypes() does not exist (PHP < 4.0.2) allow the
    // following image formats (IMG_PNG | IMG_GIF | IMG_JPG | IMG_WBMP)
	// in principal all image types which are compiled into php: 
	//   libgd, libpng, libjpeg, libungif, libtiff, libgd2, ...
	'imgtypes'        =>  array('png','gif','gd','gd2','jpeg','wbmp','xbm','xpm')
        // Todo: swf, pdf, ...

);
?>
