<?php
/* 
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */

define('PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED', 0);
define('PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED',  1);
define('PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED',   2);

define('PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET',   0);
define('PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED', 1);
define('PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED', 2);

define('PLUGIN_DOCMAN_APPROVAL_NOTIF_DISABLED',   0);
define('PLUGIN_DOCMAN_APPROVAL_NOTIF_ALLATONCE',  1);
define('PLUGIN_DOCMAN_APPROVAL_NOTIF_SEQUENTIAL', 2);

class Docman_ApprovalTable {
    var $id;
    var $date;
    var $owner;
    var $description;
    var $status;
    var $notification;

    var $reviewers;

    function Docman_ApprovalTable() {
        $this->id           = null;
        $this->date         = null;
        $this->owner        = null;
        $this->description  = null;
        $this->status       = null;
        $this->notification = null;

        $this->reviewers = array();
    }

    function setId($v) {
        $this->id = $v;
    }

    function getId() {
        return $this->id;
    }

    function setDate($v) {
        $this->date = $v;
    }

    function getDate() {
        return $this->date;
    }

    function setOwner($v) {
        $this->owner = $v;
    }

    function getOwner() {
        return $this->owner;
    }

    function setDescription($v) {
        $this->description = $v;
    }

    function getDescription() {
        return $this->description;
    }

    function setStatus($v) {
        $this->status = $v;
    }

    function getStatus() {
        return $this->status;
    }

    function setNotification($v) {
        $this->notification = $v;
    }

    function getNotification() {
        return $this->notification;
    }

    function initFromRow($row) {
        if(isset($row['item_id']))     $this->id    = $row['item_id'];
        if(isset($row['table_owner'])) $this->owner = $row['table_owner'];
        if(isset($row['date']))        $this->date  = $row['date'];
        if(isset($row['description'])) $this->description = $row['description'];
        if(isset($row['status']))      $this->status = $row['status'];
        if(isset($row['notification'])) $this->notification = $row['notification'];
    }

    // Convenient accessors
    function isDisabled() {
        if($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED) {
            return true;
        }
        return false;
    }

    function isEnabled() {
        if($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED) {
            return true;
        }
        return false;
    }

    function isClosed() {
        if($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED) {
            return true;
        }
        return false;
    }
    

    // Reviewers management
    function addReviewer(&$reviewer) {
        $this->reviewers[] =& $reviewer;
    }

    function &getReviewerArray() {
        return $this->reviewers;
    }

    function getReviewerIterator() {
        $i = new ArrayIterator($this->reviewers);
        return $i;
    }

}
class Docman_ApprovalReviewer {
    var $reviewerId;
    var $rank;
    var $reviewDate;
    var $state;
    var $comment;
    var $version;

    function Docman_ApprovalReviewer() {
        $this->reviewerId = null;
        $this->rank = null;
        $this->reviewDate = null;
        $this->state = null;
        $this->comment = null;
        $this->version = null;
    }

    function setId($v) {
        $this->reviewerId = $v;
    }
    function getId() {
        return $this->reviewerId;
    }

    function setRank($v) {
        $this->rank = $v;
    }
    function getRank() {
        return $this->rank;
    }

    function setReviewDate($v) {
        $this->reviewDate = $v;
    }
    function getReviewDate() {
        return $this->reviewDate;
    }

    function setState($v) {
        $this->state = $v;
    }
    function getState() {
        return $this->state;
    }

    function setComment($v) {
        $this->comment = $v;
    }
    function getComment() {
        return $this->comment;
    }

    function setVersion($v) {
        $this->version = $v;
    }
    function getVersion() {
        return $this->version;
    }

    function initFromRow($row) {
        if(isset($row['reviewer_id'])) $this->reviewerId = $row['reviewer_id'];
        if(isset($row['rank'])) $this->rank = $row['rank'];
        if(isset($row['date'])) $this->reviewDate = $row['date'];
        if(isset($row['state'])) $this->state = $row['state'];
        if(isset($row['comment'])) $this->comment = $row['comment'];
        if(isset($row['version'])) $this->version = $row['version'];
    }
}

?>
