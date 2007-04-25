<?php


/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Validator
*/

require_once ('common/frs/FRSPackageFactory.class.php');
require_once ('common/frs/FRSReleaseFactory.class.php');
require_once ('common/frs/FRSFileFactory.class.php');

class frsValidator {
    var $_errors;

    function addError($error) {
        if (!$this->_errors) {
            $this->_errors = array ();
        }
        $this->_errors[] = $error;
    }

    function getErrors() {
        return $this->_errors;
    }

    function isValidForCreation($release, $group_id) {
        $GLOBALS['Language']->loadLanguageMsg('file/file');
        $frspf = new FRSPackageFactory();
        $frsrf = new FRSReleaseFactory();
        $frsff = new FRSFileFactory();
        if ($release['package_id'] != 'null') {
            if (!$release['name'] || $release['name'] == '') {
                $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'rel_name_empty'));
            } else {
                //see if this package belongs to this project

                $res1 = & $frspf->getFRSPackageFromDb($release['package_id'], $group_id);
                if (!$res1 || count($res1) < 1) {
                    $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'p_not_exists'));
                } else {
                    //check if release name exists already
                    $release_exists = $frsrf->getReleaseIdByName($release['name'], $release['package_id']);
                    if (!$release_exists || count($release_exists) < 1) {
                        //now check the date
                        if (!ereg("[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}", $release['date'])) {
                            $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'data_not_parsed'));
                        }
                    } else {
                        $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'rel_name_exists'));
                    }
                }
            }
        } else {
            $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'create_p_before_rel_status'));
        }
        return count($this->_errors) ? false : true;
    }

    function isValidForUpdate($release, $group_id) {
        $GLOBALS['Language']->loadLanguageMsg('file/file');
        $frspf = new FRSPackageFactory();
        $frsrf = new FRSReleaseFactory();
        $frsff = new FRSFileFactory();
        if ($release['package_id'] != 'null') {
            if (!$release['name'] || $release['name'] == '') {
                $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'rel_name_empty'));
            } else {
                //see if this package belongs to this project
                $res1 = & $frsrf->getFRSReleaseFromDb($release['release_id'], $group_id);
                if (!$res1 || count($res1) < 1) {
                    $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'p_rel_not_yours'));

                } else {
                    if ($release['package_id'] != $res1->getPackageID()) {
                        //changing to a different package for this release
                        $res2 = $frspf->getFRSPackageFromDb($release['package_id'], $group_id);
                        if (!$res2 || count($res2) < 1) {
                            //new package_id isn't theirs
                            $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'p_not_yours'));
                        }
                    }
                    //check if release name exists already
                    if(($res1->getPackageID()!=$release['package_id']) || ($res1->getPackageID()==$release['package_id'] && $res1->getName() != $release['name'])){
                        $release_exists = $frsrf->getReleaseIdByName($release['name'], $release['package_id']);
                    }
                    if (!isset($release_exists) || count($release_exists) < 1) {
                        //now check the date
                        if (!ereg("[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}", $release['date'])) {
                            $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'data_not_parsed'));
                        }
                    } else {
                        $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'rel_name_exists'));
                    }
                }
            }
        } else {
            $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'create_p_before_rel_status'));
        }
        return count($this->_errors) ? false : true;
    }

}
?>
