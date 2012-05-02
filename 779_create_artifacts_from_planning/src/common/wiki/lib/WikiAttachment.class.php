<?php
/* 
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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

require_once('WikiAttachmentRevision.class.php');
require_once('WikiAttachmentDao.class.php');
//require_once('www/project/admin/permissions.php');
require_once('common/dao/CodendiDataAccess.class.php');

/**
 * Management of external ressources linked on a wiki
 *
 * This class allows to manage external ressources in wiki (non-text
 * ressources)
 * It's based on UpLoad plugin in PhpWiki and a Codendi layer in order to manage
 * versionning of files and permissions.
 * Every ressources (attachments) are stored in a directory. There is one
 * ressource directory per project. Every revisions of a file are stored in a
 * directory.
 *
 *
 * @see       WikiAttachmentRevision
 * @package   WikiService
 * @copyright STMicroelectronics, 2005
 * @author    Manuel Vacelet <manuel.vacelet-abecedaire@st.com>
 * @license   http://opensource.org/licenses/gpl-license.php GPL
 */
class WikiAttachment /* implements UGroupPermission */ {
    /**
     * Project identifier
     * @access private
     * @var    int 
     */
    public $gid;

    /**
     * Attachment name
     * @access private
     * @var    string
     */
    public $filename;

    /**
     * Attachment name in the filesystem
     * @access private
     * @var    string
     */
    public $filesystemName;

    /**
     * Attachment location (directory)
     * @access private
     * @var    string
     */
    public $basedir;

    /**
     * Wanted attachment revision
     * @access private
     * @var    WikiAttachmentRevision
     */
    public $revision;

    public $id;

    public $revisionCounter;

    /**
     * @access public
     * @param  int $gid Project identifier
     */
    public function __construct($gid=0) {
        $this->setGid($gid);
        $this->filetype = null;
        $this->filesize = null;
        $this->revisionCounter = null;
    }

    public function &getDao() {
        static $_codendi_wikiattachmentdao_instance;
        
        if(!$_codendi_wikiattachmentdao_instance) {
            $_codendi_wikiattachmentdao_instance = new WikiAttachmentDao(CodendiDataAccess::instance());
        }

        return $_codendi_wikiattachmentdao_instance;
    }

    /**
     * @access public
     * @param  Iterator
     */
    public function getAttachmentIterator($gid=null) {
        $waArray = array();
        if($gid !== null) {
            $gid = (int) $gid;
        }
        else {
            $gid = $this->gid;
        }

        $dao =& WikiAttachment::getDao();
        $dar =& $dao->getList($gid);
        
        while($row = $dar->getRow()) {
            $wa = new WikiAttachment($gid);
            $wa->setFromRow($row);
            $waArray[] =& $wa;
            unset($wa);
        }

        return new ArrayIterator($waArray);
    }

    public function getListWithCounter($gid=null, $uid=null, $limit=null) {
        if($gid !== null) {
            $gid = (int) $gid;
        } else {
            $gid = $this->gid;
        }

        $uid = (int) $uid;
                        
        $offset = 0;
        $max    = null;
        if(is_array($limit)) {
            // Due to permissions, we cannot use SQL limit
            // This will be possible when whe will have the
            // possibility to join the permission table and
            // the attachement table

            /*$qry .= sprintf(' LIMIT %d,%d',
                            $limit['offset'],
                            $limit['nb']);*/
            
            if(array_key_exists('offset', $limit)) {
                $offset = (int) $limit['offset'];
            }
            if(array_key_exists('nb', $limit)) {
                $max = (int) $limit['nb'];
            }
        }
        
        $dao =& WikiAttachment::getDao();
        $dar =& $dao->getListWithCounterOrderedByRevDate($gid);

        $i = 0;
        $j = 0; // count viewable attch for offset
        $waArray = array();
        $stop = false;
        while(($row = $dar->getRow()) && !$stop) {
            if($max !== null && $i >= $max) {
                $stop = true;
                break;
            }

            $wa = new WikiAttachment($gid);
            $wa->setFromRow($row);

            // Check for user rights
            $isAllowedToSee = false;
            if(! $wa->permissionExist() || $wa->isAutorized($uid)) {
                if($j >= $offset) {
                    $wa->setRevisionCounter($row['nb']);
                    $waArray[] =& $wa;
                    $i++;
                }
                $j++;
            }

            unset($wa);
        }

        return new ArrayIterator($waArray);
    }

    /**
     * @access public
     * @param  int $id Project identifier
     */
    public function setGid($id = 0) {
        $this->gid      = (int) $id;
        $this->basedir = $GLOBALS['sys_wiki_attachment_data_dir'].'/'.$this->gid;
    }
  
    /**
     * @access public
     * @param  string $name File name
     */
    public function setFilename($name = "") {

        if (preg_match("/[^._a-zA-Z0-9-\(\) &]/", $name)) {

            trigger_error($GLOBALS['Language']->getText('wiki_lib_attachment', 'err_alpha', array($name)), E_USER_ERROR);
        }

        $this->filename = $name;

        return true;
    }

    /**
     * Set the name of the attachment that will be used in the filesystem
     *
     * @return Boolean
     */
    public function initFilesystemName() {
        $this->filesystemName = $this->filename.'_'.time();
        return true;
    }
  
    public function getFilename() {
        return $this->filename;
    }

    /**
     * Obtain the name of the attachment as stored in the filesystem
     * Old attachments are stored in the filesystem as uploaded by the user
     * In that case filesystemName == NULL then the returned value is filename
     *
     * @return String
     */
    public function getFilesystemName() {
        if ($this->filesystemName) {
            return $this->filesystemName;
        } else {
            $this->initWithId($this->id);
            if ($this->filesystemName) {
                return $this->filesystemName;
            } else {
                return $this->getFilename();
            }
        }
    }

    public function setFile($basedir="") {
    
    }


    /**
     *
     * Classical URI:
     * URI: /wiki/uploads/102/php-mode-1.1.0.tgz/1
     * <pre>
     * Array
     * (
     *    [0] => 
     *    [1] => wiki                  Service
     *    [2] => uploads               Call script
     *    [3] => 102                   Group id
     *    [4] => php-mode-1.1.0.tgz    Attachment
     *    [5] => 1                     Revision of attachement (optionnal)
     * )
     * </pre>
     * Important note: '[5] => 0' == '[5] => ""' == last version
     *
     *
     * URI: /wiki/uploads/102/1/php-mode-1.1.0.tgz
     * This URI format is requested in order to support InterWiki links.
     * <pre>
     * Array
     * (
     *    [0] => 
     *    [1] => wiki                  Service
     *    [2] => uploads               Call script
     *    [3] => 102                   Group id
     *    [4] => 1                     Revision of attachement
     *    [5] => php-mode-1.1.0.tgz    Attachment
     * )
     * </pre>
     *
     * @access public
     * @param  string $uri Uri to access to attachment
     */
    public function setUri($uri="") {
        $uriExp = explode('/', $uri);

        $this->setGid($uriExp[3]);

        if(is_numeric($uriExp[4])) {
            $rev = (int) $uriExp[4];
            
            // Take care of possible arguments (if sth like '?' or '&' CUT!)
            if(preg_match('/([^&\?]*)(\?|&)/', $uriExp[5], $matches)){
                $filename = $matches[1];
            } else {
                $filename = $uriExp[5];
            }
            $this->setFilename(urldecode($filename));
            $file = $this->basedir.'/'.$this->filename;
        } else {
            // Take care of possible arguments (if sth like '?' or '&' CUT!)
            if(preg_match('/([^&\?]*)(\?|&)/', $uriExp[4], $matches)){
                $filename = $matches[1];
            } else {
                $filename = $uriExp[4];
            }
            $this->setFilename(urldecode($filename));
            $file = $this->basedir.'/'.$this->filename;
      
            // @TODO: prevent usage of '?' and '&' as behind. But with a
            // generical functions, in utils.php for instance.
            if(isset($uriExp[5]) && is_numeric($uriExp[5])) {
                $rev = (int) $uriExp[5];
            } else {
                $rev = $this->count();
            }
        }

        $rev -= 1;

        //$this->revision = new WikiAttachmentRevision($this->gid);
        $this->revision = new WikiAttachmentRevision();
        $this->revision->setGid($this->gid);
        $this->revision->setAttachmentId($this->getId());
        $this->revision->setRevision($rev);
        $this->revision->dbFetch();
        $this->revision->log(user_getid());
    }

    public function exist() {
        return is_dir($this->basedir.'/'.$this->getFilesystemName());
    }

    /**
     * Check if the status of the attachment is active
     * Active means that the delete_date is null
     *
     * @return Boolean
     */
    public function isActive() {
        $dao = WikiAttachment::getDao();
        $dar = $dao->read($this->id);
        if ($dar && !$dar->isError()) {
            $row = $dar->getRow();
            return ($row['delete_date'] == null);
        }
        return false;
    }

    public function dbadd() {
        $dao =& WikiAttachment::getDao();
        $created = $dao->create($this->gid, $this->getFilename(), $this->getFilesystemName());

        if(!$created) {            
            trigger_error($GLOBALS['Language']->getText('wiki_lib_attachment', 
                                                        'err_insert'),
                          E_USER_ERROR);
            return false;
        }
        else {
            return true;
        }
    }

    public function create() {
        // Create wiki attachment directory for current project
        if(!is_dir($this->basedir)){
            $res = mkdir($this->basedir, 0700);
            if(!$res) {
                trigger_error($GLOBALS['Language']->getText('wiki_lib_attachment', 'err_create_upload_dir'), E_USER_ERROR);
                return false;
            }
        }
    
        // Create directory where file revison will be stored
        if(!is_dir($this->basedir.'/'.$this->getFilesystemName())){
            $res = mkdir($this->basedir.'/'.$this->getFilesystemName(), 0700);
            if(!$res) {
                trigger_error($GLOBALS['Language']->getText('wiki_lib_attachment', 'err_create_file_dir'), E_USER_ERROR);
                return false;
            }
        }

        return $this->dbadd();
    }

    public function getId() {
        if(!is_numeric($this->id)) {
            $dao =& WikiAttachment::getDao();
            $dar =& $dao->getIdFromFilename($this->gid, $this->getFilename());

            if($dar->rowCount() > 1) {
                trigger_error($GLOBALS['Language']->getText('wiki_lib_attachment', 'err_multi_id'), E_USER_ERROR);
                return -1;
            } else {
                $row =& $dar->getRow();
                $this->id = $row['id'];
            }
        }

        return $this->id;
    }

    /**
     *
     * 
     *
     * @access
     * @param
     * @return
     */
    function createRevision($userfile_name, $userfile_size, $userfile_type, $userfile_tmpname) {    
        if(!$this->setFilename(urldecode($userfile_name))) {
            return -1;
        }

        if ($this->getId()) {
            $this->initWithId($this->getId());
        } elseif(!$this->initFilesystemName()) {
            return -1;
        }

        if(!$this->exist()) {
            if(!$this->create()) {	
                return -1;
            }
        }

        $att_rev = new WikiAttachmentRevision($this->gid);
    
        $att_rev->setFilename($this->getFilesystemName());
        $att_rev->setOwnerId(user_getid());
        $att_rev->setAttachmentId($this->getId());
        $att_rev->setMimeType($userfile_type);
        $att_rev->setDate(time());

        if(!$att_rev->setSize($userfile_size))
            return -1;

        if(!$att_rev->create($userfile_tmpname))    
            return -1;

        return $att_rev->getRevision();
    }
  
    function initWithId($id=0) {
        $this->id = (int) $id;
        $dao =& WikiAttachment::getDao();
        $dar =& $dao->read($this->id);
        $this->setFromRow($dar->getRow());
    }

    function setFromRow($row) {
        $this->id       = $row['id'];
        $this->setGid($row['group_id']);
        $this->filename = $row['name'];
        if (isset($row['filesystem_name'])) {
            $this->filesystemName = $row['filesystem_name'];
        }
    }

    function setRevisionCounter($nb) {
        $this->revisionCounter = (int) $nb;
    }
    
    /**
     * @access
     * @param
     * @return
     */
    function validate() {
        // Validate Group id
        $pm = ProjectManager::instance();
        $go = $pm->getProject($this->gid);
        if(!$go) 
            exit_no_group();
   
        // Validate filename   
        if(!is_dir($this->basedir.'/'.$this->getFilesystemName())){
            return false;
            //      print "error ".$this->basedir.'/'.$this->filename;
        }

        return true;
    }
  
  

    function htmlDump() {    
        $this->revision->htmlDump();
    }

    function count() {
        if($this->revisionCounter === null) {
            $this->getId();
            $waIter =& WikiAttachmentRevision::getRevisionIterator($this->gid, $this->id);
            $this->revisionCounter = $waIter->count();
        }
        return $this->revisionCounter;
    }

    /**
     * @access public
     */
    public function permissionExist() {
        require_once('www/project/admin/permissions.php');
        return (permission_exist('WIKIATTACHMENT_READ', $this->id));
    }


    /**
     * @access public
     */
    public function isAutorized($uid) {            
        require_once('www/project/admin/permissions.php');
        return ($this->permissionExist() == false || permission_is_authorized('WIKIATTACHMENT_READ', $this->id, $uid, $this->gid));
    }

  
    /**
     *@access public
     */
    public function setPermissions($groups) {
        global $feedback;

        list ($ret, $feedback) = permission_process_selection_form($this->gid, 
                                                                   'WIKIATTACHMENT_READ', 
                                                                   $this->id, 
                                                                   $groups);
        return $ret;
    }

    /**
     *@access public
     */
    public function resetPermissions() {
        return permission_clear_all($this->gid, 
                                    'WIKIATTACHMENT_READ', 
                                    $this->id);
    }

    /**
     * Mark the attachment as deleted, no physical remove from the FS until the purge
     *
     * @return Boolean
     */
    public function deleteAttachment() {
        if ($this->isActive()) {
            $dao = $this->getDao();
            return $dao->delete($this->id);
        }
        return false;
    }

    /**
     * Mark all project attachments as deleted, no physical remove from the FS until the purge
     *
     * @param Integer $groupId Id of the conserned project
     *
     * @return Boolean
     */
    public function deleteProjectAttachments($groupId = null) {
        $deleteStatus = true;
        if($groupId !== null) {
            $groupId = (int) $groupId;
        }
        else {
            $groupId = $this->gid;
        }
        $wai = $this->getAttachmentIterator($groupId);
        $wai->rewind();
        while($wai->valid()) {
            $wa = $wai->current();
            if ($wa->isActive()) {
                $deleteStatus = $wa->deleteAttachment() && $deleteStatus;
            }
            $wai->next();
        }
        return $deleteStatus;
    }

    /**
     * List all attachments deleted but not already purged
     *
     * @param Integer $groupId
     * @param Integer $offset
     * @param Integer $limit
     *
     * @return Boolean
     */
    public function listPendingAttachments($groupId, $offset, $limit) {
        $dao = $this->getDao();
        return $dao->searchAttachmentToPurge($_SERVER['REQUEST_TIME'], $groupId, $offset, $limit);
    }

    /**
     * Purge the attachments from FS and DB
     *
     * @param Integer $time
     *
     * @return Boolean
     */
    public function purgeAttachments($time) {
        $dao = $this->getDao();
        $dar = $dao->searchAttachmentToPurge($time);
        if ($dar && !$dar->isError()) {
            $purgeState = true;
            if ($dar->rowCount() > 0) {
                foreach ($dar as $row) {
                    $attachment = new WikiAttachment($this->gid);
                    $attachment->setFromRow($row);
                    $purgeState = $purgeState & $attachment->purgeAttachment();
                }
            }
            return $purgeState;
        }
        return false;
    }


    /**
     * Erase from the file system one attachment with its all version
     *
     * @return Boolean
     */
    public function purgeAttachment() {
        if($this->exist()){
            $attachmentPath = $this->basedir.'/'.$this->getFilesystemName();
            $dirAttachment = new DirectoryIterator($attachmentPath);
            foreach ($dirAttachment as $version) {
                if (!$version->isDot()) {
                    if (!unlink($version->getPathname())) {
                        return false;
                    }
                }
            }
            if (!rmdir($attachmentPath)) {
                return false;
            }
        }
        $dao = $this->getDao();
        if (!$dao->setPurgeDate($this->id, $_SERVER['REQUEST_TIME'])) {
            return false;
        }
        return true;
    }

    /**
     * Restore wiki attachment
     *
     * @param Integer $id
     *
     * @return Boolean
     */
    public function restoreDeletedAttachment($id) {
        $dao = $this->getDao();
        $this->initWithId($id);
        if($this->exist() && !$this->isActive()) {
            return $dao->restoreAttachment($id);
        }
        
        return false;
    }
}

?>
