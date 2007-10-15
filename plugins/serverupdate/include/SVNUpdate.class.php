<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * SVNUpdate
 */

/**
 * This class is based on the svn version 1.1
 * If the svn server is in 1.2, some command
 * may have changed. Be careful of that.
 *
 */

//$Language->loadLanguageMsg('svnupdate/svnupdate');

require_once("SVNCommit.class.php");
require_once("SVNCommitedFile.class.php");

// Waiting PHP 5, we use define as 'class constant'
define("XML_COMMITS", "log");
define("XML_COMMIT", "logentry");
define("XML_COMMIT_REVISION", "revision");
define("XML_COMMIT_AUTHOR", "author");
define("XML_COMMIT_DATE", "date");
define("XML_COMMIT_PATHS", "paths");
define("XML_COMMIT_PATH", "path");
define("XML_COMMIT_ACTION_PATH", "action");
define("XML_COMMIT_MESSAGE", "msg");

define("UPGRADE_SCRIPT_PATH", "/src/updates/scripts/");

class SVNUpdate {
    
    /**
     * @var string the directory of the working copy
     */
    var $workingCopyDirectory;
    /**
     * @var string the repository
     */
    var $repository;
    var $branch;
    /**
     * @var string the actual revision of the working copy
     */
    var $workingCopyRevision;
    /**
     * @var array the commits done on the repository from the last update (from the actual revision)
     */
    var $commits;
    
    /**
     * SVNUpdate constructor
     * Set the current informations (repository, actual revision and available commits)
     * 
     * @param string the directory of the working copy
     */
    function SVNUpdate($workingCopyDirectory) {
        $this->setWorkingCopyDirectory($workingCopyDirectory);
        
        $svn_infos = $this->getSVNInfos();
        $this->setWorkingCopyRevision($svn_infos['revision']);
        $this->setRepository($svn_infos['repository']);
        
        $this->setCommits($this->_getAllSVNCommit());
        
    }
    
    function getWorkingCopyDirectory() {
        return $this->workingCopyDirectory;
    }
    function setWorkingCopyDirectory($workingCopyDirectory) {
        $this->workingCopyDirectory = $workingCopyDirectory;
    }
    function getRepository() {
        return $this->repository;
    }
    function setRepository($repository) {
        $this->repository = $repository;
        $url = parse_url($repository);
        // we remove /svnroot/codex from path to retrieve the branch
        $els = explode('/', $url['path']);
        array_shift($els); array_shift($els); array_shift($els);
        $this->branch = '/'.implode('/', $els);
    }
    function getBranch() {
        return $this->branch;
    }
    function getWorkingCopyRevision() {
        return $this->revision;
    }
    function setWorkingCopyRevision($revision) {
        $this->revision = $revision;
    }
    function getCommits() {
        return $this->commits;
    }
    function setCommits($commits) {
        $this->commits = $commits;
    }
    
    /**
     * Returns all the available commits from the last update,
     * using the svn log command (based on the svn version 1.1)
     *
     * @return array all the available commits from the last update. Array of {SVNCommit} Object.
     */
    function _getAllSVNCommit() {
        $commits = array();
        $xml_commits = $this->_SVNCommand("svn log --xml -v -r ".($this->getWorkingCopyRevision() + 1).":HEAD ".$this->getWorkingCopyDirectory());
        $commits = $this->_setCommitsFromXML($xml_commits);
        return $commits;
    }
    
    /**
     * Returns all the available upgrade scripts :
     * - located in the scripts directory, and
     * - has the extension .php, and
     * - under version control, and
     * - implementing the generic Upgrade Script CodeXUpgrade and
     * - is not CodeXUpgrade itself !
     *
     * @return array all the script-upgrades found in the script directory and well formed. Array of {UpgradeScript} Object
     */
    function getAllUpgrades() {
        $upgrades = array();
        $script_path = $this->getWorkingCopyDirectory().UPGRADE_SCRIPT_PATH;
        if (is_dir($script_path)) {
            if ($dir_handle = opendir($script_path)) {
                // get the list of files under version control
                $files_under_subversion_control = $this->_getFilesUnderSVNControl($script_path);
                // walk the files found in the directory
                while (($file = readdir($dir_handle)) !== false) {
                    if (!in_array($file, array('.', '..', 'CodeXUpgrade.class.php'))) {
                        $path_parts = pathinfo($file);
                        if ($path_parts['extension'] == 'php') {
                            // To avoid the case where another file would be here (not a so called script)
                            // we test if the file is under version control
                            if (in_array($file, $files_under_subversion_control)) {
                                $classname = UpgradeScript::className($file);
                                $current_script = new UpgradeScript($this->getBranch());
                                $current_script->setPath($this->getBranch() . UPGRADE_SCRIPT_PATH . $file);
                                $current_script->setClassname($classname);
                                if ($current_script->isWellImplemented()) {
                                    $current_script->_setAllExecutions();
                                    $upgrades[$file] = $current_script;    
                                } else {
                                    unset($current_script);
                                }
                            }
                        }
                    }
                }
                closedir($dir_handle);
            }
        }
        ksort($upgrades);
        return $upgrades;
    }
    
    
    /**
     * Get the Commit with the revision Id $revision in the pool of available commits.
     * 
     * @param int $revision the revision number we want to retrieve
     * @return {SVNCommit} Object the SVNCommit corresponding to the revision number $revision
     */
    function getSVNCommit($revision) {
        $commits = $this->getCommits();
        if (count($commits) > 0) {
            $i = 0;
            $current_commit = $commits[$i];
            while ($i<count($commits)-1 && $current_commit->getRevision() != $revision) {
                $i++;
                $current_commit = $commits[$i];
            }
            if (($i == count($commits) - 1) && $current_commit->getRevision() != $revision) {
                $current_commit = null;
            }
        } else {
            $current_commit = null;
        }
        return $current_commit;
    }
    
    /**
     * Get the Commits with the revision Id between $revision_begin and $revision_end in the pool of available commits.
     * 
     * @param int $revision_begin the first revision number we want to retrieve
     * @param int $revision_end the last revision number we want to retrieve
     * @return array of {SVNCommit} Object the SVNCommits between the revision number $revision_begin and $revision_end
     */
    function getSVNCommitsBetween($revision_begin, $revision_end) {
        $commits = $this->getCommits();
        $commits_between = array();
        if (count($commits) > 0) {
            $i = 0;
            $current_commit = $commits[$i];
            while (($i<(count($commits)-1)) && ($current_commit->getRevision() <= $revision_end)) {
                if ($current_commit->getRevision() >= $revision_begin && $current_commit->getRevision() <= $revision_end) {
                    $commits_between[] = $current_commit;
                }
                $i++;
                $current_commit = $commits[$i];
            }
            if (($i == (count($commits) - 1)) && ($current_commit->getRevision() <= $revision_end)) {
                $commits_between[] = $current_commit;
            }
        }
        return $commits_between;
    }
    
    /**
     * Test if the file $file is present in the revisions done after $revision
     *
     * @param Object{CommitedFile} the file to check
     * @param int $revision the revision number to test after
     * @return boolean true if $file is present in any revision after $revision, false otherwise
     */
    function isPresentInFurtherRevision($file, $revision) {
        $commits = $this->getCommits();
        $last_commit = $commits[count($commits)-1];
        $last_revision = $last_commit->getRevision();
        $commits_between = $this->getSVNCommitsBetween($revision, $last_revision);
        foreach ($commits_between as $commit) {
            if (($commit->getRevision() > $revision) && ($commit->isFilePartOfCommit($file))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test if the file $file is deleted in the revisions done after $revision
     *
     * @param Object{CommitedFile} the file to check
     * @param int $revision the revision number to test after
     * @return boolean true if $file is deleted in any revision after $revision, false otherwise
     */
    function isDeletedInFurtherRevision($file, $revision) {
        $commits = $this->getCommits();
        $last_commit = $commits[count($commits)-1];
        $last_revision = $last_commit->getRevision();
        $commits_between = $this->getSVNCommitsBetween($revision, $last_revision);
        foreach ($commits_between as $commit) {
            if (($commit->getRevision() > $revision) && ($commit->isFilePartOfCommit($file))) {
                $further_file = $commit->getFileByPath($file->getPath());
                if ($further_file->getAction() == "D") {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns the number of the revision in which the script located at $script_path must be executed, or null if the script must'nt be executed
     *
     * @param string $script_path the path of the script
     * @return the revision in which the script must be executed, or null if it must not be executed
     */
    function getRevisionInWhichFileMustBeExecuted($script_path) {
        $revision_to_be_executed = null;
        $scriptsRevisions = $this->_getScriptsRevisions();
        
        if (array_key_exists($script_path, $scriptsRevisions)) {
            $revisions_containing_file = $scriptsRevisions[$script_path];
            // Algorithm :
            // We walk the revision of the current update in which the script is present
            // A script must be executed if and only if it is created in the current update
            // and so, it must be executed at the revision of its last modification
            foreach($revisions_containing_file as $rev_number) {
                $current_commit = $this->getSVNCommit($rev_number);
                $current_file = $current_commit->getFileByPath($script_path);
                if ($current_file != null) {
                    if ($current_file->getAction() == "A") {
                        // A added script must be executed at the current revision (except if it is modified further)
                        $revision_to_be_executed = $rev_number;
                    } elseif ($current_file->getAction() == "D") {
                        // A deleted script must not be executed
                        $revision_to_be_executed = null;
                    } elseif ($current_file->getAction() == "M") {
                        // A modified script can only be executed if it has been created in the same update
                        if ($revision_to_be_executed != null) {
                            $revision_to_be_executed = $rev_number;
                        }
                    }
                }
            }
        } else {
            // the file is not part of any commit in the current update
            $revision_to_be_executed = null;
        }
        return $revision_to_be_executed;
    }
    
    
    /**
     * Returns the life cycle (during the current update) of scripts present in this update
     * Returns an array : 
     * array('script_001' => array($revision => $action,
     *                             $revision => $action),
     *       'script_002' => array($revision => $action,
     *                             $revision => $action,
     *                             $revision => $action))
     *
     * @return array the array representing the life cycle of the scripts present in the update, during the update
     */
    function getScriptsLifeCycle() {
        $scriptsLifeCycle = array();
        $all_commits = $this->getCommits();
        foreach($all_commits as $commit) {
            $scripts = $commit->getScripts();
            foreach($scripts as $script) {
                $scriptsLifeCycle[$script->getPath()][$commit->getRevision()] = $script->getAction();
            }
        }
        return $scriptsLifeCycle;
    }
    
    
    /** 
     * Returns an array of scripts, with all the revisions in which the script is present (is part of the commit)
     * array('script1'=>(22, 26, 46),
     *       'script2'=>(24),
     *       'script3'=>(46,47,48,65))
     * @return array an array of all scripts in the current update, with all the revisions in which the script is present
     */
    function _getScriptsRevisions() {
        $scriptsRevisions = array();
        $all_commits = $this->getCommits();
        foreach($all_commits as $commit) {
            $scripts = $commit->getScripts();
            foreach($scripts as $script) {
                $scriptsRevisions[$script->getPath()][] = $commit->getRevision();
            }
        }
        return $scriptsRevisions;
    }
    
    
    
    
    /**
     * Simulate the update of the working copy from the current revision up to revision $revision
     *
     * @param int $revision the revision up to we want simulate the update
     * @return string the output generated by the svn command
     */
    function testUpdate($revision) {
        $simulation = $this->_SVNCommand("svn merge --dry-run -r ".$this->getWorkingCopyRevision().":".$revision." ".$this->getRepository()." ".$this->getWorkingCopyDirectory());
        return $simulation;
    }
    
    
    /**
     * Execute the update of the working copy from the current revision up to revision $revision
     *
     * @param int $revision the revision up to we want update
     * @return string the output generated by the svn command
     */
    function updateServer($revision) {
        $update = $this->_SVNCommand("cd ".$this->getWorkingCopyDirectory()."; svn update -r ".$revision);
        return $update;
    }
    

//
// SAX PARSER
//
// Specific functions aimed to parse the result of a 'svn log' command, returning XML.
// and based on the SVN 1.1 version.
//
// The XML expected looks like :
// 
// <log>
//  <logentry revision="2926">
//   <author>guerin</author>
//   <date>2006-04-11T16:14:52.159548Z</date>
//   <paths>
//    <path action="M">/dev/trunk/src/www/include/trove.php</path>
//    <path action="M">/dev/trunk/src/www/softwaremap/trove_list.php</path>
//    <path action="M">/dev/trunk/src/etc/local.inc.dist</path>
//   </paths>
//   <msg>Add parameter in local.inc to specify the default trove category displayed
//        in the Software Map welcome page.
//   </msg>
//  </logentry>
//  <logentry revision="2925">
//   <author>guerin</author>
//   <date>2006-04-11T16:03:41.273381Z</date>
//   <paths>
//    <path action="M">/dev/trunk/src/db/mysql/database_initvalues.sql</path>
//   </paths>
//   <msg>Fixed typo</msg>
//  </logentry>
// </log>


    /** 
     * Function startElement : called when SAX parser encounter a start tag
     * 
     * @param {resource} $parser a reference on the xml_parser object
     * @param string $element_name the name of the starting element (send by the parser)
     * @param array $attributes array of XML attributes ($attributes['attribute_name'] => $attribute_value)
     */
    function startElement($parser, $element_name, $attributes) {
        global $current_commit, $commits, $current_files, $current_file, $current_data, $current_message;
        
        switch ($element_name) {
            case XML_COMMITS:
                break;
            case XML_COMMIT:
                $current_commit = new SVNCommit();
                $current_commit->setRevision($attributes[XML_COMMIT_REVISION]);
                break;
            case XML_COMMIT_AUTHOR:
                break;
            case XML_COMMIT_DATE:
                break;
            case XML_COMMIT_PATHS:
                $current_files = array();
                break;
            case XML_COMMIT_PATH:
                $current_file = new SVNCommitedFile();
                $current_file->setAction($attributes[XML_COMMIT_ACTION_PATH]);
                break;
            case XML_COMMIT_MESSAGE:
                $current_message = "";
                break;
            default:
                break;
        }
        
    }
    
    /** 
     * Function endElement : called when SAX parser encounter an end tag
     * 
     * @param {resource} $parser a reference on the xml_parser object
     * @param string $element_name the name of the ending element (send by the parser)
     */
    function endElement($parser, $element_name) {
        global $current_commit, $commits, $current_files, $current_file, $current_data, $current_message;
        
        switch ($element_name) {
            case XML_COMMITS:
                break;
            case XML_COMMIT:
                $commits[] = $current_commit;
                break;
            case XML_COMMIT_AUTHOR:
                $current_commit->setAuthor($current_data);
                break;
            case XML_COMMIT_DATE:
                $current_commit->setDate($current_data);
                break;
            case XML_COMMIT_PATHS:
                $current_commit->setFiles($current_files);
                break;
            case XML_COMMIT_PATH:
                // a file can be a simple file or a script
                if (UpgradeScript::isInScriptDirectory($this->getBranch(), $current_data) &&
                    ! UpgradeScript::isTheGenericScript($this->getBranch(), $current_data)) {
                    $current_script = new UpgradeScript($this->getBranch());
                    $current_script->setAction($current_file->getAction());
                    $current_script->setPath($current_data);
                    $current_script->setClassname(UpgradeScript::getClassNameFromPath($current_data));
                    $current_script->_setAllExecutions();
                    // a script!
                    $current_file = $current_script;
                } else {
                    // a simple file
                    $current_file->setPath($current_data);
                }
                $current_files[] = $current_file;
                break;
            case XML_COMMIT_MESSAGE:
                //echo "#".$current_data."<br>";
                $current_commit->setMessage($current_message);
                break;
            default:
                break;
        }
    }
    
    /** 
     * Function characterData : called when SAX parser encounter a text data
     * 
     * @param {resource} $parser a reference on the xml_parser object
     * @param string $data the value of the string found (send by the parser)
     */
    function characterData($parser, $data) {
        global $current_commit, $commits, $current_files, $current_file, $current_data, $current_message;

        $current_data = $data;
        $current_message .= $data;

    } 

    
    
    
    
    /**
     * Map the result of the svn log command on an array of SVNCommits
     *
     * @param string $xml_commits the xml string returned by the svn log command
     * @return array an array of {SVNCommit} Object
     */
    function _setCommitsFromXML($xml_commits) {
        global $commits;
        
        $commits = array();
        
        /*if ($dom_document = domxml_open_mem($xml_commits)) {
            $root = $dom_document->document_element();
            $node_array = $root->get_elements_by_tagname(XML_COMMITS);
            for ($i = 0; $i<count($node_array); $i++) {
                   $node = $node_array[$i];
                   $commit = new SVNCommit();
                   $commit->setCommitFromXML($node);
                   $commits[] = $commit;
            }
        }*/
        
        
        $xml_parser = xml_parser_create();
        xml_set_element_handler($xml_parser, array($this, "startElement"), array($this, "endElement"));
        xml_set_character_data_handler($xml_parser, array($this, "characterData"));
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 0);
        if (!xml_parse($xml_parser, $xml_commits)) {
            die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($xml_parser)), xml_get_current_line_number($xml_parser)));
        }
        xml_parser_free($xml_parser);
        
        
        return $commits;
    }
    
    
    /**
     * Execute a SVN command and return the result of this command
     *
     * @access private
     * @param string the entire svn command, as you would type it in a terminal
     * @return string the result of the command
     */
    function _SVNCommand($command) {
        $commandReturn = "";
        if (trim($command) != "") {
            ob_start();
            passthru($command);
            $commandReturn = ob_get_contents();
            ob_end_clean();
        }
        return $commandReturn;
    }
    
    /**
     * Returns the informations given by the svn info command
     * 
     * This class is based on the 1.1 SVN version, and the svn info command returns something like this :
     * Path: .
     * URL: https://partners.xrce.xerox.com/svnroot/codex/support/CX_2_6_SUP
     * Repository UUID: df09dd2a-99fe-0310-ba0d-faeadf64de00
     * Revision: 2599
     * Node Kind: directory
     * Schedule: normal
     * Last Changed Author: guerin
     * Last Changed Rev: 2553
     * Last Changed Date: 2006-02-14 16:20:23 +0100 (Tue, 14 Feb 2006)
     *
     * @return array the infos given by the svn info command
     */
    function getSVNInfos() {
        $svn_infos = array();
        $infos = $this->_SVNCommand("cd ".$this->getWorkingCopyDirectory()." ; svn info ; cd -");
        
        $svn_infos['revision'] = $this->_getSVNInfoRevision($infos);
        $svn_infos['repository'] = $this->_getSVNInfoRepository($infos);
        
        return $svn_infos;
    }
    
    
    /**
     * Returns the last revision of the working copy
     *
     * @access private 
     * @return int the actual revision of the working copy, or 0 if an error occurs
     */
    function _getSVNInfoRevision($svn_infos) {
        $actual_revision = 0;
        eregi('Revision:[ ]*([0-9]*)', $svn_infos, $regs);
        if ($regs[1] && is_numeric($regs[1])) {
            $actual_revision = $regs[1];
        }
        return $actual_revision;
    }
    
    /**
     * Returns the repository corresponding to the working copy
     *
     * @access private
     * @return string the url repository corresponding to the working copy
     */
    function _getSVNInfoRepository($svn_infos) {
        eregi('URL:[ ]*([a-zA-Z0-9:/.-_]*)', $svn_infos, $regs);
        return $regs[1];
    }
    
    /**
     * Returns an array of files and directories part of the dir $path and under SVN version control
     *
     * Warning : this function is based on the subversion 1.1 version
     * So be carefull if you upgrade the subversion version.
     *
     * @access private
     * @return string the path we want to get the inside files
     */
    function _getFilesUnderSVNControl($path) {
        $files = array();
        $ls_return = $this->_SVNCommand("svn list ".realpath($path));
        $files = explode("\n", $ls_return);
        // the end of the ls return command is a line separator, so the last element of the array can be null
        if ($files[count($files) - 1] == "") {
            array_pop($files);
        }
        return $files;
    }
    
    
    /**
     * Returns the lines of an SVN output that potentially contains a conflict
     * Based on SVN 1.1 version.
     *
     * @static
     *
     * @param string $merge_output the output returned by SVN command
     * @return array an array of string representing the conflicted lines of the SVN output
     */
    function getConflictedLines($merge_output) {
        $lines = explode("\n", $merge_output);
        $conflited_lines = array();
        foreach ($lines as $line) {
            if (trim($line) != "") {
                switch ($line[0]) {
                    case "A":
                    case "D":
                    case "U":
                    case "G":
                        break;
                    default:
                        $conflited_lines[] = $line;
                        break;
                }
            }
        }
        return $conflited_lines;
    }
    
}

?>
