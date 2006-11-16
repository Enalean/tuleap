<?php
// +----------------------------------------------------------------------+
// | PEAR :: Cache                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
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
// |          Ian Eure <ieure@php.net>                                    |
// +----------------------------------------------------------------------+
//
// $Id: trifile.php,v 1.1 2004/06/21 08:39:38 rurban Exp $

require_once 'Cache/Container/file.php';

/**
 * Tri-file cache.
 *
 * This cache container stores files with no special encoding to reduce overhead.
 * Expiration & user data are stored in seperate files, prefixed with a '.' and
 * suffixed with '.exp' & '.dat' respectively.
 *
 * See http://atomized.org/PEAR/Cache_trifile.html for more information.
 *
 * @author Ian Eure <ieure@php.net>
 * @version 1.0
 */
class Cache_Container_trifile extends Cache_Container_file {
    /**
     * Fetch cached file.
     *
     * @param string $id Cache ID to fetch
     * @param string $group Group to fetch from
     * @return array 1-dimensional array in the format: expiration,data,userdata
     */
    function fetch($id, $group)
    {
        $file = $this->getFilename($id, $group);
        if (!file_exists($file))
            return array(NULL, NULL, NULL);
        
        return array(
                file_get_contents($this->_getExpFile($file)),
                file_get_contents($file),
                file_get_contents($this->_getUDFile($file))
        );
    }
    
    /**
     * Get the file to store cache data in.
     *
     * @return string Cache data file name
     * @access private
     */
    function _getFile($file)
    {
        $dir = dirname($file);
        $file = basename($file);
        return $dir.'/.'.$file;
    }
    
    /**
     * Get the file to store expiration data in.
     *
     * @return string Expiration data file name
     * @access private
     */
    function _getExpFile($file)
    {
        return $this->_getFile($file).'.exp';
    }
    
    /**
     * Get the file to store user data in.
     *
     * @return string User data file name
     * @access private
     */
    function _getUDFile($file)
    {
        return $this->_getFile($file).'.dat';
    }
    
    /**
     * Cache file
     *
     * @param string $id Cache ID
     * @param mixed $cachedata Data to cache
     * @param mixed $expires When the data expires
     * @param string $group Cache group to store data in
     * @param mixed $userdata Additional data to store
     * @return boolean true on success, false otherwise
     */
    function save($id, $cachedata, $expires, $group, $userdata)
    {
        $this->flushPreload($id, $group);

        $file = $this->getFilename($id, $group);
        if (PEAR::isError($res = $this->_saveData($file, $cachedata))) {
            return $res;
        }
        if (PEAR::isError($res = $this->_saveData($this->_getExpFile($file), $expires))) {
            return $res;
        }
        if(PEAR::isError($res = $this->_saveData($this->_getUDFile($file), $userData))) {
            return $res;
        }

        return true;
    }

    /**
     * Save data in a file
     *
     * @param string $file File to save data in
     * @param string $data Data to save
     * @return mixed true on success, Cache_Error otherwise
     */
    function _saveData($file, $data) {
        // Save data
        if (!($fh = @fopen($file, 'wb')))
            return new Cache_Error("Can't access '$file' to store cache data. Check access rights and path.", __FILE__, __LINE__);
        
        if ($this->fileLocking) {
            flock($fh, LOCK_EX);
        }
        
        fwrite($fh, $data);
        
        if($this->fileLocking) {
            flock($fh, LOCK_UN);
        }
        
        fclose($fh);
        return true;
    }
}

?>