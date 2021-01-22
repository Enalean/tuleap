<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
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

require_once __DIR__ . '/../../www/include/utils.php';

class CrossReference
{
    public $id;
    public $userId;
    public $createdAt;

    public $refSourceId;
    public $refSourceGid;
    public $refSourceType;
    public $sourceUrl;
    public $sourceKey;

    public $refTargetId;
    public $refTargetGid;
    public $refTargetType;
    public $targetUrl;
    public $targetKey;
    public $insertTargetType;
    public $insertSourceType;

    /**
     * Constructor
     *
     */
    public function __construct($refSourceId, $refSourceGid, $refSourceType, $refSourceKey, $refTargetId, $refTargetGid, $refTargetType, $refTargetKey, $userId)
    {
        $this->refSourceId   = $refSourceId;
        $this->refSourceGid  = $refSourceGid;
        $this->refSourceType = $refSourceType;
        $this->refTargetId   = $refTargetId;
        $this->refTargetGid  = $refTargetGid;
        $this->refTargetType = $refTargetType;
        $this->userId        = $userId;
        $this->sourceUrl     = '';
        $this->targetUrl     = '';

        $this->sourceKey        = $refSourceKey;
        $this->insertSourceType = $refSourceType;
        $this->targetKey        = $refTargetKey;
        $this->insertTargetType = $refTargetType;

        $this->computeUrls();
    }

    /** Accessors */
    public function getRefSourceId()
    {
        return $this->refSourceId;
    }
    public function getRefSourceGid()
    {
        return $this->refSourceGid;
    }
    public function getRefSourceType()
    {
        return $this->refSourceType;
    }
    public function getRefTargetId()
    {
        return $this->refTargetId;
    }
    public function getRefTargetGid()
    {
        return $this->refTargetGid;
    }
    public function getRefTargetType()
    {
        return $this->refTargetType;
    }
    public function getUserId()
    {
        return $this->userId;
    }
    public function getRefTargetUrl()
    {
        return $this->targetUrl;
    }
    public function getRefSourceUrl()
    {
        return $this->sourceUrl;
    }
    public function getRefSourceKey()
    {
        return $this->sourceKey;
    }
    public function getRefTargetKey()
    {
        return $this->targetKey;
    }
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    public function getInsertSourceType()
    {
        return $this->insertSourceType;
    }
    public function getInsertTargetType()
    {
        return $this->insertTargetType;
    }


    /**
     * Return true if current CrossReference is really "cross referenced" with $crossref
     *
     * @param CrossReference $crossref
     * @return bool true if current CrossReference is really "cross referenced" with $crossref
     */
    public function isCrossReferenceWith($crossref)
    {
        return $this->getRefSourceId() == $crossref->getRefTargetId() &&
               $this->getRefSourceGid() == $crossref->getRefTargetGid() &&
               $this->getRefSourceType() == $crossref->getRefTargetType() &&
               $crossref->getRefSourceId() == $this->getRefTargetId() &&
               $crossref->getRefSourceGid() == $this->getRefTargetGid() &&
               $crossref->getRefSourceType() == $this->getRefTargetType();
    }

    public function computeUrls()
    {
        $server_url  = HTTPRequest::instance()->getServerUrl();
        $group_param = '';
        if ($this->refTargetGid != 100) {
            $group_param = "&group_id=" . $this->refTargetGid;
        }
        $this->targetUrl = $server_url . "/goto?key=" . urlencode($this->targetKey) . "&val=" . urlencode($this->refTargetId) . $group_param;
        $group_param     = '';
        if ($this->refSourceGid != 100) {
            $group_param = "&group_id=" . $this->refSourceGid;
        }
        $this->sourceUrl = $server_url . "/goto?key=" . urlencode($this->sourceKey) . "&val=" . urlencode($this->refSourceId) . $group_param;
    }
}
