<?php
/*
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

class Docman_ApprovalReviewer
{
    var $reviewerId;
    var $rank;
    var $reviewDate;
    var $state;
    var $comment;
    var $version;

    function __construct()
    {
        $this->reviewerId = null;
        $this->rank = null;
        $this->reviewDate = null;
        $this->state = null;
        $this->comment = null;
        $this->version = null;
    }

    function setId($v)
    {
        $this->reviewerId = $v;
    }
    function getId()
    {
        return $this->reviewerId;
    }

    function setRank($v)
    {
        $this->rank = $v;
    }
    function getRank()
    {
        return $this->rank;
    }

    function setReviewDate($v)
    {
        $this->reviewDate = $v;
    }
    function getReviewDate()
    {
        return $this->reviewDate;
    }

    function setState($v)
    {
        $this->state = $v;
    }
    function getState()
    {
        return $this->state;
    }

    function setComment($v)
    {
        $this->comment = $v;
    }
    function getComment()
    {
        return $this->comment;
    }

    function setVersion($v)
    {
        $this->version = $v;
    }
    function getVersion()
    {
        return $this->version;
    }

    function initFromRow($row)
    {
        if (isset($row['reviewer_id'])) {
            $this->reviewerId = $row['reviewer_id'];
        }
        if (isset($row['rank'])) {
            $this->rank = $row['rank'];
        }
        if (isset($row['date'])) {
            $this->reviewDate = $row['date'];
        }
        if (isset($row['state'])) {
            $this->state = $row['state'];
        }
        if (isset($row['comment'])) {
            $this->comment = $row['comment'];
        }
        if (isset($row['version'])) {
            $this->version = $row['version'];
        }
    }
}
