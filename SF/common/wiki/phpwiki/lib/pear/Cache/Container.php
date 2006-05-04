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
// |          Sebastian Bergmann <sb@sebastian-bergmann.de>               |
// |          Christian Stocker <chregu@phant.ch>                         |
// +----------------------------------------------------------------------+
//
// $Id: Container.php,v 1.3 2004/06/21 08:39:38 rurban Exp $

require_once 'Cache/Error.php';

/**
* Common base class of all cache storage container.
* 
* To speed up things we do a preload you should know about, otherwise it might 
* play you a trick. The Cache controller classes (Cache/Cache, Cache/Output, ...)
* usually do something like is (isCached($id) && !isExpired($id)) return $container->load($id).
* if you implement isCached(), isExpired() and load() straight ahead, each of this 
* functions will result in a storage medium (db, file,...) access. This generates too much load. 
* Now, a simple speculative preload should saves time in most cases. Whenever 
* one of the mentioned methods is invoked we preload the cached dataset into class variables.
* That means that we have only one storage medium access for the sequence
*  (isCached($id) && !isExpired($id)) return $container->load($id).
* The bad thing is that the preloaded data might be outdated meanwhile, which is 
* unlikely but for you power users, be warned. If you do not want the preload 
* you should switch it off by setting the class variable $preload to false. Anyway, this is 
* not recommended!
* 
* @author   Ulf Wendel <ulf.wendel@phpdoc.de>
* @version  $Id: Container.php,v 1.3 2004/06/21 08:39:38 rurban Exp $
* @package  Cache
* @access   public
* @abstract
*/
class Cache_Container {

    /**
    * Flag indicating wheter to preload datasets.
    *
    * See the class description for more details.
    *
    * @var  boolean
    */
    var $preload = true;

    /**
    * ID of a preloaded dataset
    *
    * @var  string
    */
    var $id = '';

    /**
    * Cache group of a preloaded dataset
    *
    * @var  string
    */
    var $group = '';

    /**
    * Expiration timestamp of a preloaded dataset.
    * 
    * @var  integer 0 means never, endless
    */
    var $expires = 0;

    /**
    * Value of a preloaded dataset.
    * 
    * @var  string
    */
    var $cachedata = '';

    /**
    * Preloaded userdata field.
    * 
    * @var  string
    */
    var $userdata = '';

    /**
    * Flag indicating that the dataset requested for preloading is unknown.
    *  
    * @var  boolean
    */
    var $unknown = true;

    /**
    * Encoding mode for cache data: base64 or addslashes() (slash).
    *
    * @var  string  base64 or slash
    */
    var $encoding_mode = 'base64';
    
    /**
    * Highwater mark - maximum space required by all cache entries.
    * 
    * Whenever the garbage collection runs it checks the amount of space
    * required by all cache entries. If it's more than n (highwater) bytes
    * the garbage collection deletes as many entries as necessary to reach the
    * lowwater mark. 
    * 
    * @var  int
    * @see  lowwater
    */
    var $highwater = 2048000; 
    
    
    /**
    * Lowwater mark
    *
    * @var  int
    * @see  highwater
    */
    var $lowwater = 1536000;
    
    
    /**
    * Options that can be set in every derived class using it's constructor.
    * 
    * @var  array
    */
    var $allowed_options = array('encoding_mode', 'highwater', 'lowwater');
    
    
    /**
    * Loads a dataset from the cache.
    * 
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   mixed   dataset value or NULL on failure
    * @access   public
    */
    function load($id, $group) {
        if ($this->preload) {
            if ($this->id != $id || $this->group != $group)
                $this->preload($id, $group);

            return $this->cachedata;
        } else {
            list( , $data, ) = $this->fetch($id, $group);
            return $data;
        }
    } // end func load

    /**
    * Returns the userdata field of a cached data set.
    *
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   string  userdata
    * @access   public
    */
    function getUserdata($id, $group) {
        if ($this->preload) {
            if ($this->id != $id || $this->group != $group)
                $this->preload($id, $group);
                
            return $this->userdata;
        } else {
            list( , , $userdata) = $this->fetch($id, $group);
            return $userdata;
        }
    } // end func getUserdata

    /**
    * Checks if a dataset is expired.
    * 
    * @param    string  dataset ID
    * @param    string  cache group
    * @param    integer maximum age timestamp
    * @return   boolean 
    * @access   public
    */
    function isExpired($id, $group, $max_age) {
        if ($this->preload) {
          if ($this->id != $id || $this->group != $group)
            $this->preload($id, $group);
          
          if ($this->unknown)
            return false;
        } else {
            // check if at all it is cached
            if (!$this->isCached($id, $group))
                return false;
                
            // I'm lazy...
            list($this->expires, , ) = $this->fetch($id, $group);
        }

        // endless
        if (0 == $this->expires)
            return false;

        // you feel fine, Ulf?
        if ($expired  = ($this->expires <= time() || ($max_age && ($this->expires <= $max_age))) ) {

           $this->remove($id, $group);
           $this->flushPreload();
        }
        return $expired;
    } // end func isExpired

    /**
    * Checks if a dataset is cached.
    *
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   boolean
    */
    function isCached($id, $group) {
        if ($this->preload) {
            if ($this->id != $id || $this->group != $group)
                $this->preload($id, $group);

            return !($this->unknown);
        } else {
            return $this->idExists($id, $group);
        }
    } // end func isCached

    //
    // abstract methods
    //

    /**
    * Fetches a dataset from the storage medium.
    *
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   array   format: [expire date, cached data, user data]
    * @throws   Cache_Error
    * @abstract
    */
    function fetch($id, $group) {
        return array(NULL, NULL, NULL);
    } // end func fetch

    /**
    * Stores a dataset.
    * 
    * @param    string  dataset ID
    * @param    mixed   data to store
    * @param    mixed   userdefined expire date
    * @param    string  cache group
    * @param    string  additional userdefined data
    * @return   boolean
    * @throws   Cache_Error
    * @access   public
    * @abstract
    */
    function save($id, $data, $expire, $group, $userdata) {
        // QUESTION: Should we update the preload buffer instead?
        // Don't think so as the sequence save()/load() is unlikely.
        $this->flushPreload($id, $group);

        return NULL;
    } // end func save

    /**
    * Removes a dataset.
    * 
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   boolean  
    * @access   public
    * @abstract
    */     
    function remove($id, $group) {
        $this->flushPreload($id, $group);
        return NULL;
    } // end func remove

    /**
    * Flushes the cache - removes all caches datasets from the cache.
    * 
    * @param    string      If a cache group is given only the group will be flushed
    * @return   integer     Number of removed datasets, -1 on failure
    * @access   public
    * @abstract
    */
    function flush($group) {
        $this->flushPreload();
        return NULL;
    } // end func flush

    /**
    * Checks if a dataset exists.
    * 
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   boolean 
    * @access   public
    * @abstract
    */
    function idExists($id, $group) {
        return NULL;
    } // end func idExists

    /**
    * Starts the garbage collection.
    * 
    * @access   public
    * @abstract
    */
    function garbageCollection() {
        $this->flushPreload();
    } // end func garbageCollection

    /**
    * Does a speculative preload of a dataset
    *
    * @param    string  dataset ID
    * @param    string  cache group
    * @return   boolean
    */ 
    function preload($id, $group) {
        // whatever happens, remember the preloaded ID
        $this->id = $id;
        $this->group = $group;        

        list($this->expires, $this->cachedata, $this->userdata) = $this->fetch($id, $group);

        if (NULL === $this->expires) {
            // Uuups, unknown ID
            $this->flushPreload();

            return false;
        }

        $this->unknown = false;

        return true;
    } // end func preload

    /**
    * Flushes the internal preload buffer.
    *
    * save(), remove() and flush() must call this method
    * to preevent differences between the preloaded values and 
    * the real cache contents.
    *
    * @param    string  dataset ID, if left out the preloaded values will be flushed. 
    *                   If given the preloaded values will only be flushed if they are
    *                   equal to the given id and group
    * @param    string  cache group
    * @see  preload()
    */
    function flushPreload($id = '', $group = 'default') {
        if (!$id || ($this->id == $id && $this->group == $group)) {
            // clear the internal preload values
            $this->id = '';
            $this->group = '';
            $this->cachedata = '';
            $this->userdata = '';
            $this->expires = -1;
            $this->unknown = true;
        }
    } // end func flushPreload

    /**
    * Imports the requested datafields as object variables if allowed
    * 
    * @param    array   List of fields to be imported as object variables
    * @param    array   List of allowed datafields
    */
    function setOptions($requested, $allowed) {
        foreach ($allowed as $k => $field)
            if (isset($requested[$field]))
                $this->$field = $requested[$field];
                
    } // end func setOptions

    /**
    * Encodes the data for the storage container.
    * 
    * @var  mixed data to encode
    */
    function encode($data) {
        if ('base64' == $this->encoding_mode) 
            return base64_encode(serialize($data));
        else 
            return serialize($data);
    } // end func encode

    
    /**
    * Decodes the data from the storage container.
    * 
    * @var  mixed
    */
    function decode($data) {
        if ('base64' == $this->encoding_mode)
            return unserialize(base64_decode($data));
        else
            return unserialize($data);
    } // end func decode

    
    /**
    * Translates human readable/relative times in unixtime
    *
    * @param  mixed   can be in the following formats:
    *               human readable          : yyyymmddhhmm[ss]] eg: 20010308095100
    *               relative in seconds (1) : +xx              eg: +10
    *               relative in seconds (2) : x <  946681200   eg: 10
    *               absolute unixtime       : x < 2147483648   eg: 2147483648
    *               see comments in code for details
    * @return integer unix timestamp
    */
    function getExpiresAbsolute($expires)
    {
        if (!$expires)
            return 0;
        //for api-compatibility, one has not to provide a "+",
        // if integer is < 946681200 (= Jan 01 2000 00:00:00)
        if ('+' == $expires[0] || $expires < 946681200)
        {
            return(time() + $expires);
        }
        //if integer is < 100000000000 (= in 3140 years),
        // it must be an absolut unixtime
        // (since the "human readable" definition asks for a higher number)
        elseif ($expires < 100000000000)
        {
            return $expires;
        }
        // else it's "human readable";
        else
        {
            $year = substr($expires, 0, 4);
            $month = substr($expires, 4, 2);
            $day = substr($expires, 6, 2);
            $hour = substr($expires, 8, 2);
            $minute = substr($expires, 10, 2);
            $second = substr($expires, 12, 2);
            return mktime($hour, $minute, $second, $month, $day, $year);
        }
        
    } // end func getExpireAbsolute
    
} // end class Container
?>
