<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 *
 *
 *
 * Cross Reference class
 * Stores a Cross Reference as extracted from some user text.
 */

require_once __DIR__ . '/../../www/include/utils.php';

class CrossReference
{
    var $id;
    var $userId;
    var $createdAt;

    var $refSourceId;
    var $refSourceGid;
    var $refSourceType;
    var $sourceUrl;
    var $sourceKey;

    var $refTargetId;
    var $refTargetGid;
    var $refTargetType;
    var $targetUrl;
    var $targetKey;
    var $insertTargetType;
    var $insertSourceType;

    /**
     * Constructor
     *
     */
    function __construct($refSourceId, $refSourceGid, $refSourceType, $refSourceKey, $refTargetId, $refTargetGid, $refTargetType, $refTargetKey, $userId)
    {
        $this->refSourceId=$refSourceId;
        $this->refSourceGid=$refSourceGid;
        $this->refSourceType=$refSourceType;
        $this->refTargetId= $refTargetId;
        $this->refTargetGid= $refTargetGid;
        $this->refTargetType= $refTargetType;
        $this->userId=$userId;
        $this->sourceUrl='';
        $this->targetUrl='';

        $this->sourceKey= $refSourceKey;
        $this->insertSourceType = $refSourceType;
        $this->targetKey = $refTargetKey;
        $this->insertTargetType = $refTargetType;

        $this->computeUrls();
    }

    /** Accessors */
    function getRefSourceId()
    {
        return $this->refSourceId;
    }
    function getRefSourceGid()
    {
        return $this->refSourceGid;
    }
    function getRefSourceType()
    {
        return $this->refSourceType;
    }
    function getRefTargetId()
    {
        return $this->refTargetId;
    }
    function getRefTargetGid()
    {
        return $this->refTargetGid;
    }
    function getRefTargetType()
    {
        return $this->refTargetType;
    }
    function getUserId()
    {
        return $this->userId;
    }
    function getRefTargetUrl()
    {
        return $this->targetUrl;
    }
    function getRefSourceUrl()
    {
        return $this->sourceUrl;
    }
    function getRefSourceKey()
    {
        return $this->sourceKey;
    }
    function getRefTargetKey()
    {
        return $this->targetKey;
    }
    function getCreatedAt()
    {
        return $this->createdAt;
    }
    function getInsertSourceType()
    {
        return $this->insertSourceType;
    }
    function getInsertTargetType()
    {
        return $this->insertTargetType;
    }


    /**
     * Return true if current CrossReference is really "cross referenced" with $crossref
     *
     * @param CrossReference $crossref
     * @return bool true if current CrossReference is really "cross referenced" with $crossref
     */
    function isCrossReferenceWith($crossref)
    {
        return $this->getRefSourceId() == $crossref->getRefTargetId() &&
               $this->getRefSourceGid() == $crossref->getRefTargetGid() &&
               $this->getRefSourceType() == $crossref->getRefTargetType() &&
               $crossref->getRefSourceId() == $this->getRefTargetId() &&
               $crossref->getRefSourceGid() == $this->getRefTargetGid() &&
               $crossref->getRefSourceType() == $this->getRefTargetType();
    }

    function computeUrls()
    {
        $server_url  = HTTPRequest::instance()->getServerUrl();
        $group_param = '';
        if ($this->refTargetGid!=100) {
            $group_param="&group_id=".$this->refTargetGid;
        }
        $this->targetUrl= $server_url."/goto?key=".urlencode($this->targetKey)."&val=".urlencode($this->refTargetId).$group_param;
        $group_param = '';
        if ($this->refSourceGid!=100) {
            $group_param="&group_id=".$this->refSourceGid;
        }
        $this->sourceUrl= $server_url."/goto?key=".urlencode($this->sourceKey)."&val=".urlencode($this->refSourceId).$group_param;
    }
}
