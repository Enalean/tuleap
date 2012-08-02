<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/include/Error.class.php');
require_once('FRSReleaseFactory.class.php');
require_once('common/include/Codendi_HTTP_Download.php');

class FRSFile extends Error {

    const EVT_CREATE  = 301;
    const EVT_UPDATE  = 302;
    const EVT_DELETE  = 303;
    const EVT_RESTORE = 304;

	/**
     * @var int $file_id the ID of this FRSFile
     */
    var $file_id;	
    /**
     * @var String $filename the name of this FRSFile
     */
    var $filename;
    /**
     * @var String $filepath the full path where the file is created  
     */
    var $filepath;
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
    /**
     * @var string $status the status of this FRSFile (A=>Active; D=>Deleted)
     */
    var $status;
    /**
     * @var string $computed_md5 hash of the file computed in server side
     */
    var $computed_md5;
    /**
     * @var string $reference_md5 hash of the file submited by user (calculated in client side)
     */
    var $reference_md5;
    /**
     * @var integer $user_id id of user that created the file
     */
    var $user_id;
    /**
     * @var string $file_location the full path of this FRSFile
     */
    var $file_location;

    /**
     * @var FRSRelease $release The release the file belongs to
     */
    protected $release;
    
    function FRSFile($data_array = null) {
        $this->file_id       = null;
        $this->filename     = null;
        $this->filepath     = null;
        $this->release_id    = null;
        $this->type_id       = null;
        $this->processor_id  = null;
        $this->release_time  = null;
        $this->file_size     = null;
        $this->post_date     = null;
        $this->status        = null;
        $this->computed_md5  = null;
        $this->reference_md5 = null;
        $this->user_id       = null;
        $this->file_location = null;

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

    /**
     * Obtain the name of the file as stored in filesystem
     * Old files are stored in the filesystem as uploaded by the user
     * In that case filepath == NULL then the returned value is filename
     *
     * @return String
     */
    function getFilePath() {
        if ($this->filepath == null) {
            return $this->filename;
        } else {
            return $this->filepath;
        }
    }

    function setFilePath($filepath) {
        $this->filepath = $filepath;
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
    
    function setFileLocation($location) {
        $this->file_location = $location;
    }
    
    /**
     * Returns the location of the file on the server
     *
     * @global $GLOBALS['ftp_frs_dir_prefix']
     * @return string the location of this file on the server
     */
    function getFileLocation() {
        if ($this->file_location == null) {
            $group = $this->getGroup();
            $group_unix_name = $group->getUnixName(false);
            $basename = $this->getFilePath();
            $this->file_location = $GLOBALS['ftp_frs_dir_prefix'].'/'.$group_unix_name.'/'.$basename;
        }
        return $this->file_location;
    }

    function getFileSize() {
        if ($this->file_size == null) {
            $file_location = $this->getFileLocation();
            // Use file_utils to provide a workaround for the 2 GB limitation
            $this->file_size = file_utils_get_size($file_location);
        }
        return $this->file_size;
    }
    
    function setFileSize($file_size) {
        $this->file_size = $file_size;
    }
    
    static function convertBytesToKbytes($size_in_bytes, $decimals_precision = 0) {
        $size_in_kbytes = $size_in_bytes / 1024;
        
        $decimal_separator = $GLOBALS['Language']->getText('system','decimal_separator');
        $thousand_separator = $GLOBALS['Language']->getText('system','thousand_separator'); 
        // because I don't know how to specify a space in a .tab file
        if ($thousand_separator == "' '") {
            $thousand_separator = ' ';  
        }
        return number_format($size_in_kbytes, $decimals_precision, $decimal_separator, $thousand_separator); 
    }

    function getDisplayFileSize() {
        $decimals_precision = 0;
        if ($this->getFileSize() < 1024) {
            $decimals_precision = 2;
        }
        return $this->convertBytesToKbytes($this->getFileSize(), $decimals_precision);
    }
    
    function getPostDate() {
        return $this->post_date;
    }
    
    function setPostDate($post_date) {
        $this->post_date = (int) $post_date;
    }
    
    function getStatus() {
        return $this->status;
    }
    
    function setStatus($status) {
        $this->status = $status;
    }
    
    function isActive() {
        return ($this->status == 'A');
    }
    
    function isDeleted() {
        return ($this->status == 'D');
    }

    function setComputedMd5($computedMd5) {
        $this->computed_md5 = $computedMd5;
    }

    function getComputedMd5() {
        return $this->computed_md5;
    }

    function setReferenceMd5($referenceMd5) {
        $this->reference_md5 = $referenceMd5;
    }

    function getReferenceMd5() {
        return $this->reference_md5;
    }

    function setUserID($userId) {
        $this->user_id = $userId;
    }

    function getUserID() {
        return $this->user_id;
    }

    function setRelease($release) {
        $this->release    = $release;
        $this->release_id = $release->getReleaseID();
    }

    function getRelease() {
        return $this->release;
    }

	function initFromArray($array) {
		if (isset($array['file_id']))       $this->setFileID($array['file_id']);
		if (isset($array['filename']))      $this->setFileName($array['filename']);
        if (isset($array['filepath']))      $this->setFilePath($array['filepath']);
		if (isset($array['release_id']))    $this->setReleaseID($array['release_id']);
        if (isset($array['type_id']))       $this->setTypeID($array['type_id']);
        if (isset($array['processor_id']))  $this->setProcessorID($array['processor_id']);
        if (isset($array['release_time']))  $this->setReleaseTime($array['release_time']);
        if (isset($array['file_size']))     $this->setFileSize($array['file_size']);
        if (isset($array['post_date']))     $this->setPostDate($array['post_date']);
        if (isset($array['status']))        $this->setStatus($array['status']);
        if (isset($array['computed_md5']))  $this->setComputedMd5($array['computed_md5']);
        if (isset($array['reference_md5'])) $this->setReferenceMd5($array['reference_md5']);
        if (isset($array['user_id']))       $this->setUserID($array['user_id']);
    }

    function toArray() {
        $array = array();
        $array['file_id']       = $this->getFileID();
        $array['filename']      = $this->getFileName();
        $array['filepath']      = $this->getFilePath();
        $array['release_id']    = $this->getReleaseID();
        $array['type_id']       = $this->getTypeID();
        $array['processor_id']  = $this->getProcessorID();
        $array['release_time']  = $this->getReleaseTime();
        $array['file_location'] = $this->getFileLocation();
        $array['file_size']     = $this->getFileSize();
        $array['post_date']     = $this->getPostDate();
        $array['status']        = $this->getStatus();
        $array['computed_md5']  = $this->getComputedMd5();
        $array['reference_md5'] = $this->getReferenceMd5();
        $array['user_id']       = $this->getUserID();
        
        return $array;
    }
    
    var $dao;
    
    function &_getFRSFileDao() {
        if (!$this->dao) {
            $this->dao = new FRSFileDao(CodendiDataAccess::instance());
        }
        return $this->dao;
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
     * Get the Package ID of this File
     *
     * @return int the packahe ID of this file
     */
    function getPackageID() {
        // retrieve the release the file belongs to
        $release_id = $this->getReleaseID();
        $release_fact = new FRSReleaseFactory();
        $release =& $release_fact->getFRSReleaseFromDb($release_id);
        $package_id = $release->getPackageID();
        return $package_id;
    }
    
    
    /**
     * Get the Group (the project) of this File
     *
     * @return Object{Group} the group the file belongs to
     */
    function getGroup() {
        $pm = ProjectManager::instance();
        // retrieve the release the file belongs to
        $release_id = $this->getReleaseID();
        $release_fact = new FRSReleaseFactory();
        $release = $release_fact->getFRSReleaseFromDb($release_id, null, null, FRSReleaseDao::INCLUDE_DELETED);
        $group_id = $release->getGroupID();
        $group = $pm->getProject($group_id);
        return $group;
    }

    /**
     * Returns the content of the file, in a raw resource
     *
     * +2GB safe
     *
     * @return mixed the content of the file
     */
    function getContent($offset=0, $size=-1) {
        if ($size == -1) {
            $size = $this->getFileSize();
        }
        $path = PHP_BigFile::stream(realpath($this->getFileLocation()));
        return file_get_contents($path, false, NULL, $offset, $size);
    }

    /**
     * Log the download of the file in the log system
     * 
     * Only log one download attempt per file/user/hour. Driven by SOAP:getFileChunk
     * in order to reduce the amount of download attempt logged.
     * 
     * @param int $user_id the user that download the file (if 0, the current user will be taken)
     * @return boolean true if there is no error, false otherwise
     */
    function LogDownload($user_id = 0) {
        if ($user_id == 0) {
            $user_id = UserManager::instance()->getCurrentUser()->getId();
        }
        $time = $_SERVER['REQUEST_TIME'] - 3600;
        $dao  = $this->_getFrsFileDao();
        if (!$dao->existsDownloadLogSince($this->getFileID(), $user_id, $time)) {
            return $dao->logDownload($this, $user_id);
        }
        return true;
    }

    /**
     * userCanDownload : determine if the user can download the file or not
     *
     * WARNING : for the moment, user can download the file if the user can view the package and can view the release the file belongs to.  
     *  
     * @param int $user_id the ID of the user. If $user_id is 0, then we take the current user.
     * @return boolean true if the user has permissions to download the file, false otherwise
     */
    function userCanDownload($user_id = 0) {
        if ($user_id == 0) {
            $user_id = user_getid();
        }
        
        $user = UserManager::instance()->getUserById($user_id);
        if ($user) {
            if ($user->isSuperUser()) {
                return true;
            }
        }
        
        $user_can_download = false;
        if (! $this->isDeleted()) { 
            $group = $this->getGroup();
            $group_id = $group->getID();
            if (permission_exist('RELEASE_READ', $this->getReleaseID())) {
                if (permission_is_authorized('RELEASE_READ',$this->getReleaseID(),$user_id,$group_id)) {
                    $user_can_download = true;
                } 
            } else if (permission_is_authorized('PACKAGE_READ',$this->getPackageID(),$user_id,$group_id)) {
                $user_can_download = true;
            }
        }
        return $user_can_download; 	
    }
  
    /**
     * download : download the file, i.e. print it to stdout
     *
     * WARNING : this function does not check permissions, nor does it log the download  
     *  
     * @return boolean true if the user has permissions to download the file, false otherwise
     */
    function download() {
        $file_location = $this->getFileLocation();
        if (false && class_exists('Codendi_HTTP_Download')) {
            return !PEAR::isError(Codendi_HTTP_Download::staticSend(array(
                'file'               => $this->getFileLocation(),
                'cache'              => false,
                'contentdisposition' => array(HTTP_DOWNLOAD_ATTACHMENT, basename($this->getFileName())),
                'buffersize'         => 8192,
                )
            ));
        } else { //old school to be removed in 4.2
        $file_size = $this->getFileSize();

        // Make sure this URL is not cached anywhere otherwise download
        // would be wrong
        header('Expires: Mon, 26 Nov 1962 00:00:00 GMT');
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');
        
        header("Content-Type: application/octet-stream");
        header('Content-Disposition: attachment; filename="'. basename($this->getFileName()) .'"');
        if ($file_size > 0){
            header("Content-Length:  $file_size");
        }
        header("Content-Transfer-Encoding: binary\n");

        //reset time limit for big files
        set_time_limit(0);
        flush();

        // Now transfer the file to the client

        // Check the 2 GB limit (2^31 -1)
        if ($file_size > 2147483647) {
            if ($fp=popen("/bin/cat $file_location","rb")) {
                $blocksize = (2 << 20); //2M chunks
                while(!feof($fp)) {
                    print(fread($fp, $blocksize));
                }
                flush();
                pclose($fp);
            } else return false;
        } else if (readfile($file_location) == false) {
            return false;
        }
            
        return true;
        }
    }
    
    /**
     * Returns the HTML content for tooltip when hover a reference with the nature file
     * @returns string HTML content for file tooltip
     */
    function getReferenceTooltip() {
        $tooltip = '';
        $rf = new FRSReleaseFactory();
        $pf = new FRSPackageFactory();
        $release_id = $this->getReleaseID();
        $release = $rf->getFRSReleaseFromDb($release_id);
        $package_id = $release->getPackageID();
        $package = $pf->getFRSPackageFromDb($package_id);
        $tooltip .= '<table>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'filename') . ':</strong></td>';
        $tooltip .= '  <td>'.basename($this->getFileName()).'</td>';
        $tooltip .= ' </tr>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_ref_tooltip', 'package_release') . ':</strong></td>';
        $tooltip .= '  <td>'.$package->getName().' / '.$release->getName().'</td>';
        $tooltip .= ' </tr>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_showfiles', 'date') . ':</strong></td>';
        $tooltip .= '  <td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt_short'), $release->getReleaseDate()).'</td>';
        $tooltip .= ' </tr>';
        $tooltip .= ' <tr>';
        $tooltip .= '  <td><strong>' . $GLOBALS['Language']->getText('file_showfiles', 'size') . ':</strong></td>';
        $tooltip .= '  <td>'.$this->getDisplayFileSize().'</td>';
        $tooltip .= ' </tr>';
        $tooltip .= '</table>';
        return $tooltip;
    }
    
}

?>
