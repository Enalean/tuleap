<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Tuleap\FRS;

use FRSPackageFactory;
use FRSReleaseFactory;

class FRSValidator
{
    private $_errors = [];

    public function addError($error)
    {
        if (!$this->_errors) {
            $this->_errors = array ();
        }
        $this->_errors[] = $error;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function isValidForCreation($release, $group_id)
    {
        $frspf = new FRSPackageFactory();
        $frsrf = new FRSReleaseFactory();
        if (isset($release['package_id']) && $release['package_id'] != 'null') {
            if (! isset($release['name']) || !$release['name'] || $release['name'] == '') {
                $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'rel_name_empty'));
            } else {
                //see if this package belongs to this project

                $res1 = $frspf->getFRSPackageFromDb($release['package_id'], $group_id);
                if ($res1 === null) {
                    $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'p_not_exists'));
                } else {
                    //check if release name exists already
                    $release_exists = $frsrf->getReleaseIdByName($release['name'], $release['package_id']);
                    if (!$release_exists || count($release_exists) < 1) {
                        //now check the date
                        if (! preg_match("/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/", $release['date'])) {
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

        return count($this->_errors) === 0;
    }

    public function isValidForUpdate($release, $group_id)
    {
        $frspf = new FRSPackageFactory();
        $frsrf = new FRSReleaseFactory();
        if (isset($release['package_id']) && $release['package_id'] != 'null') {
            if (!isset($release['name']) || !$release['name'] || $release['name'] == '') {
                $this->addError($GLOBALS['Language']->getText('file_admin_editreleases', 'rel_name_empty'));
            } else {
                //see if this package belongs to this project
                $res1 = $frsrf->getFRSReleaseFromDb($release['release_id'], $group_id);
                if (! $res1 || $res1 === null) {
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
                    if (($res1->getPackageID() != $release['package_id']) || ($res1->getPackageID() == $release['package_id'] && $res1->getName() != $release['name'])) {
                        $release_exists = $frsrf->getReleaseIdByName($release['name'], $release['package_id']);
                    }
                    if (!isset($release_exists) || count($release_exists) < 1) {
                        //now check the date
                        if (! preg_match("/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/", $release['date'])) {
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
        return count($this->_errors) === 0;
    }
}
