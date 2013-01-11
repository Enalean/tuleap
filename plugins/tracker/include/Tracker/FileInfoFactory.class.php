<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'Tracker_FileInfo.class.php';
require_once 'dao/Tracker_FileInfoDao.class.php';

class Tracker_FileInfoFactory {
    /**
     * @var Tracker_FileInfoDao
     */
    private $dao;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(Tracker_FileInfoDao $dao, Tracker_FormElementFactory $formelement_factory) {
        $this->dao                 = $dao;
        $this->formelement_factory = $formelement_factory;
    }

    /**
     *
     * @param type $id
     *
     * @return Tracker_FileInfo
     */
    public function getById($id) {
        $row = $this->dao->searchById($id)->getRow();
        if ($row) {
            $field_id = $this->dao->searchFieldIdByFileInfoId($id);
            $field    = $this->formelement_factory->getFormElementById($field_id);
            if ($field && $field->isUsed()) {
                return new Tracker_FileInfo(
                    $row['id'], 
                    $field,
                    $row['submitted_by'],
                    $row['description'],
                    $row['filename'],
                    $row['filesize'],
                    $row['filetype']
                );
            }
        }
        return null;
    }
}

?>
