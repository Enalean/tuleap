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
    public $reviewerId;
    public $rank;
    public $reviewDate;
    public $state;
    public $comment;
    public $version;

    public function __construct()
    {
        $this->reviewerId = null;
        $this->rank = null;
        $this->reviewDate = null;
        $this->state = null;
        $this->comment = null;
        $this->version = null;
    }

    public function setId($v)
    {
        $this->reviewerId = $v;
    }
    public function getId()
    {
        return $this->reviewerId;
    }

    public function setRank($v)
    {
        $this->rank = $v;
    }
    public function getRank()
    {
        return $this->rank;
    }

    public function setReviewDate($v)
    {
        $this->reviewDate = $v;
    }
    public function getReviewDate()
    {
        return $this->reviewDate;
    }

    public function setState($v)
    {
        $this->state = $v;
    }
    public function getState()
    {
        return $this->state;
    }

    public function setComment($v)
    {
        $this->comment = $v;
    }
    public function getComment()
    {
        return $this->comment;
    }

    public function setVersion($v)
    {
        $this->version = $v;
    }
    public function getVersion()
    {
        return $this->version;
    }

    public function initFromRow($row)
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
