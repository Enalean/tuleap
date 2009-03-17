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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('common/backend/Backend.class.php');


class BackendSVN extends Backend {


    /**
     * Constructor
     */
    protected function __construct() {
        parent::__construct();
    }


    /**
     * Create project SVN repository
     * If the directory already exists, nothing is done.
     * @return true if repo is successfully created, false otherwise
     */
    public function createProjectSVN($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;
        $unix_group_name=$project->getUnixName(false); // May contain upper-case letters
        $svn_dir=$GLOBALS['svn_prefix']."/".$unix_group_name;
        if (!is_dir($svn_dir)) {
            // Let's create a SVN repository for this group
            if (!mkdir($svn_dir)) {
                $this->log("Can't create project SVN dir: $svn_dir");
                return false;
            }
            system($GLOBALS['svnadmin_cmd']." create $svn_dir --fs-type fsfs");

            $this->recurseChownChgrp($svn_dir,$GLOBALS['sys_http_user'],$unix_group_name);
            system("chmod g+rw $svn_dir");
        }


        // Put in place the svn post-commit hook for email notification
        // if not present (if the file does not exist it is created)
        if ($project->isSVNTracked()) {
            $filename = "$svn_dir/hooks/post-commit";
            $update_hook=false;
            if (! is_file($filename)) {
                $update_hook=true;
            } else {
                $file_array=file($filename);
                if (!in_array($this->block_marker_start,$file_array)) {
                    $update_hook=true;
                }
            }
            if ($update_hook) {
                $command  ='REPOS="$1"'."\n";
                $command .='REV="$2"'."\n";
                $command .=$GLOBALS['codex_bin_prefix'].'/commit-email.pl "$REPOS" "$REV" 2>&1 >/dev/null';
                $this->addBlock($filename,$command);
                $this->chown($filename,$GLOBALS['sys_http_user']);
                $this->chgrp($filename,$unix_group_name);
           chmod("$filename",0775);
            }
        }

        if (!$this->UpdateSVNAccess()) {
            $this->log("Can't update SVN access file");
            return false;
        }

        return true;
    }



    public function UpdateSVNAccess(){
        return true;
    }


    /**
     * Archive SVN repository: stores a tgz in temp dir, and remove the directory
     */
    public function archiveProjectSVN($group_id) {
        $project=$this->_getProjectManager()->getProject($group_id);
        if (!$project) return false;
        $mydir=$GLOBALS['svn_prefix']."/".$project->getUnixName(false);
        $backupfile=$GLOBALS['tmp_dir']."/".$project->getUnixName(false)."-svn.tgz";

        if (is_dir($mydir)) {
            system("cd ".$GLOBALS['svn_prefix']."; tar cfz $backupfile ".$project->getUnixName(false));
            chmod($backupfile,0600);
            $this->recurseDeleteInDir($mydir);
            rmdir($mydir);
            return true;
       } else return false;
     }

}

?>