<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * @version $Id: SVNCommit.class.php 2658 2006-04-11 14:36:30Z mnazaria $
 *
 * SVNUpdate
 */

//$Language->loadLanguageMsg('svnupdate/svnupdate');

require_once('SVNCommitMetaData.class.php');

class SVNCommit {
    
    /**
     * @var int $revision the revision of this commmit
     */
    var $revision;
    
    /**
     * @var string $author the author of this commit
     */
    var $author;
    
    /**
     * @var string $date the date of this commmit
     */
    var $date;
    
    /**
    * @var array{SVNCommitedFile} $files array of SVNCommitedFiles : the files impacted by this commit 
     */
    var $files;
    
    /**
     * @var string the commit message
     */
    var $message;
    
    /**
    * @var {SVNCommitMetaData} Object the meta data of this commit
     */
    var $metaData;
    
    /**
     * SVNCommit constructor
     */
    function SVNCommit() {
        $this->metaData = new SVNCommitMetaData();
    }
    
    function getRevision() {
        return $this->revision;
    }
    function setRevision($revision) {
        $this->revision = $revision;
    }
    function getAuthor() {
        return $this->author;
    }
    function setAuthor($author) {
        $this->author = $author;
    }
    function getDate() {
        return $this->date;
    }
    function setDate($date) {
        $this->date = $date;
    }
    function getMessage() {
        return $this->message;
    }
    function setMessage($message) {
        $this->message = $message;
        $this->metaData->setMetaData($message);
    }
    function getFiles() {
        return $this->files;
    }
    function setFiles($files) {
        $this->files = $files;
    }
    function getMetaData() {
        return $this->metaData;
    }
    function setMetaData($metaData) {
        $this->metaData = $metaData;
    }
    
    
    function getLevel() {
        return $this->metaData->getLevel();
    }
    function needManualUpdate() {
        return $this->metaData->getNeedManualUpdate();
    }
    function containsDBUpdate() {
        return $this->metaData->containsDBUpdate();
    }
    
    /**
     * Returns the commited files that are "upgrade scripts"
     *
     * @return array{UpgradeScript} the array of UpgradeScript present in this commit
     */
    function getScripts() {
        $scripts = array();
        $commited_files = $this->getFiles();
        foreach($commited_files as $current_file) {
            if (is_a($current_file, "UpgradeScript")) {
                $scripts[] = $current_file;
            }
        }
        return $scripts;
    }
    
    /**
     * @deprecated
     *
     * Deprecated. DOMXML will no longer be used in PHP 5.
     * So we use the SAX parser to keep the compatibility
     *
     */
    function setCommitFromXML($logentry_node) {
        // Get the revision
        $revision = $logentry_node->get_attribute("revision");
        //echo "REVISION=".$revision."<hr>";
        // Get the author
        $author_node_array = $logentry_node->get_elements_by_tagname("author");
        $author = $author_node_array[0]->get_content();
        //echo "AUTHOR=".$author."<hr>";
        // Get the date
        $date_node_array = $logentry_node->get_elements_by_tagname("date");
        $date = $date_node_array[0]->get_content();
        //echo "DATE=".$date."<hr>";
        // Get the commit message
        $message_node_array = $logentry_node->get_elements_by_tagname("msg");
        $message = $message_node_array[0]->get_content();
        
        // Get the paths
        $paths_node_array = $logentry_node->get_elements_by_tagname("paths");
        $paths_node = $paths_node_array[0];
        $path_node_array = $paths_node->get_elements_by_tagname("path");
        $files = array();
        for ($i = 0; $i<count($path_node_array); $i++) {
               $path_node = $path_node_array[$i];
               $path = $path_node->get_content();
               $action = $path_node->get_attribute("action");
               $file = new SVNCommitedFile();
               $file->setPath($path);
               $file->setAction($action);
               $files[] = $file;
        }
        
        // Set the value to the object
        $this->setRevision($revision);
        $this->setAuthor($author);
        $this->setDate($date);
        $this->setMessage($message);
        $this->setFiles($files);
        
    }
    
    
    /**
     * Test if $searched_file is part of this commit or not
     * The test is realized by testing the path.
     *
     * @param Object{SVNCommitedFile} $searched_file the file we are looking for
     * @return boolean true if $searched_file is part of the current commit, false otherwise
     */
    function isFilePartOfCommit($searched_file) {
        $files = $this->getFiles();
        foreach($files as $file) {
            if ($file->getPath() == $searched_file->getPath()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns the {SVNCommitedFile} contained in this current commit and located at $searched_path
     *
     * @param string the path of the file we are looking for
     * @return Object{SVNCommitedFile} the searched file, or null if the file was not found
     */
    function getFileByPath($searched_path) {
        $files = $this->getFiles();
        foreach($files as $file) {
            if ($file->getPath() == $searched_path) {
                return $file;
            }
        }
        return null;
    }
    

}

?>
