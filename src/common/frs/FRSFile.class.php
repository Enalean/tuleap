<?php
/**
 * GForge File Release Facility
 *
 * Copyright 2002 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id: FRSFile.class,v 1.23.2.1 2005/11/03 19:21:27 danper Exp $
 *
 * This file is part of GForge.
 *
 * GForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GForge; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/include/Error.class.php');

class FRSFile extends Error {
	
	/**
     * @var int $file_id the ID of this FRSFile
     */
    var $file_id;	
    /**
     * @var int $filename the name of this FRSFile
     */
    var $filename;
    /**
     * @var int $release_id the ID of the release this FRSFile belong to
     */
    var $release_id;
    /**
     * @var int $type_id the ID of the type of this FRSFile
     */
    var $type_id;
    /**
     * @var int $processor_id the ID of the processor to use with this FRSFile
     */
    var $processor_id;
    /**
     * @var int $release_time the ??? of this FRSFile
     */
    var $release_time;
    /**
     * @var int $file_size the size of this FRSFile
     */
    var $file_size;
    /**
     * @var int $post_date the ??? of this FRSFile
     */
    var $post_date;
    
    function FRSFile($data_array = null) {
        $this->file_id       = null;
        $this->filename     = null;
        $this->release_id    = null;
        $this->type_id       = null;
        $this->processor_id  = null;
        $this->release_time  = null;
        $this->file_size     = null;
        $this->post_date     = null;

        if ($data_array) {
            $this->initFromArray($data_array);
        }
    }
    
    function getFileID() {
        return $this->file_id;
    }
    
    function setFileID($file_id) {
        $this->file_id = (int) $file_id;
    }
    
    function getFileName() {
        return $this->filename;
    }
    
    function setFileName($filename) {
        $this->filename = $filename;
    }
    
    function getReleaseID() {
        return $this->release_id;
    }
    
    function setReleaseID($release_id) {
        $this->release_id = (int) $release_id;
    }
    
    function getTypeID() {
        return $this->type_id;
    }
    
    function setTypeID($type_id) {
        $this->type_id = (int) $type_id;
    }
    
    function getProcessorID() {
        return $this->processor_id;
    }
    
    function setProcessorID($processor_id) {
        $this->processor_id = (int) $processor_id;
    }
    
    function getReleaseTime() {
        return $this->release_time;
    }
    
    function setReleaseTime($release_time) {
        $this->release_time = (int) $release_time;
    }
    
    function getFileSize() {
        return $this->file_size;
    }
    
    function setFileSize($file_size) {
        $this->file_size = (int) $file_size;
    }
    
    function getPostDate() {
        return $this->post_date;
    }
    
    function setPostDate($post_date) {
        $this->post_date = (int) $post_date;
    }

	function initFromArray($array) {
		if (isset($array['file_id']))       $this->setFileID($array['file_id']);
		if (isset($array['filename']))     $this->setFileName($array['filename']);
		if (isset($array['release_id']))    $this->setReleaseID($array['release_id']);
        if (isset($array['type_id']))       $this->setTypeID($array['type_id']);
        if (isset($array['processor_id']))  $this->setProcessorID($array['processor_id']);
        if (isset($array['release_time']))  $this->setReleaseTime($array['release_time']);
        if (isset($array['file_size']))     $this->setFileSize($array['file_size']);
        if (isset($array['post_date']))     $this->setPostDate($array['post_date']);
    }

    function toArray() {
        $array = array();
        $array['file_id']       = $this->getFileID();
        $array['filename']     = $this->getFileName();
        $array['release_id']    = $this->getReleaseID();
        $array['type_id']       = $this->getTypeID();
        $array['processor_id']  = $this->getProcessorID();
        $array['release_time']  = $this->getReleaseTime();
        $array['file_size']     = $this->getFileSize();
        $array['post_date']     = $this->getPostDate();
        return $array;
    }
    
    /**
     * Determine if the file exists really on the server or not
     *
     * @return boolean true if the file exists on the server, false otherwise
     */
    function fileExists() {
        return file_exists($this->getFileLocation());
    }
    
    /**
     * Returns the location of the file on the server
     *
     * @global $GLOBALS['ftp_frs_dir_prefix']
     * @return string the location of this file on the server
     */
    function getFileLocation() {
        global $GLOBALS;
        
        $group = $this->getGroup();
        $group_unix_name = $group->getUnixName();
        $basename = $this->getFileName();
        $file_location = $GLOBALS['ftp_frs_dir_prefix'].'/'.$group_unix_name.'/'.$basename;
        return $file_location;
    }

    /**
     * Get the Group (the project) of this File
     *
     * @return Object{Group} the group the file belongs to
     */
    function getGroup() {
        // retrieve the release the file belongs to
        $release_id = $this->getReleaseID();
        $release_fact = new FRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        $group_id = $release->getGroupID();
        $group = new Group($group_id);
        return $group;
    }
    
    /**
     * Returns the content of the file, in a raw resource
     *
     * @return mixed the content of the file
     */
    function getContent() {
        $file_location = $this->getFileLocation();
        if ($fp = fopen($file_location,"rb")) {
            return fread($fp, filesize($file_location));
        } else {
            return null;
        }
    }

}

?>
