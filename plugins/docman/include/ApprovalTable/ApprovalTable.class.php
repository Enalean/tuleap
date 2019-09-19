<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

abstract class Docman_ApprovalTable
{
    var $id;
    var $date;
    var $owner;
    var $description;
    var $status;
    var $notification;
    var $notificationOccurence;

    var $approvalState;
    var $customizable;
    var $reviewers;

    private $is_potentially_corrupted = false;

    function __construct()
    {
        $this->id                 = null;
        $this->date               = null;
        $this->owner              = null;
        $this->description        = null;
        $this->status             = null;
        $this->notification       = null;
        $this->notificationOccurence = null;

        $this->approvalState      = null;
        $this->customizable       = true;
        $this->reviewers          = array();
    }

    function setId($v)
    {
        $this->id = $v;
    }

    function getId()
    {
        return $this->id;
    }

    function setDate($v)
    {
        $this->date = $v;
    }

    function getDate()
    {
        return $this->date;
    }

    function setOwner($v)
    {
        $this->owner = $v;
    }

    function getOwner()
    {
        return $this->owner;
    }

    function setDescription($v)
    {
        $this->description = $v;
    }

    function getDescription()
    {
        return $this->description;
    }

    function setStatus($v)
    {
        $this->status = $v;
    }

    function getStatus()
    {
        return $this->status;
    }

    function setNotification($v)
    {
        $this->notification = $v;
    }

    function getNotification()
    {
        return $this->notification;
    }

    function setNotificationOccurence($v)
    {
        $this->notificationOccurence = $v;
    }

    function getNotificationOccurence()
    {
        return $this->notificationOccurence;
    }

    function setCustomizable($v)
    {
        $this->customizable = $v;
    }

    function getCustomizable()
    {
        return $this->customizable;
    }

    function getApprovalState()
    {
        return $this->approvalState;
    }

    public function isPotentiallyCorrupted()
    {
        return (bool) $this->is_potentially_corrupted;
    }

    function initFromRow($row)
    {
        if (isset($row['table_id'])) {
            $this->id    = $row['table_id'];
        }
        if (isset($row['table_owner'])) {
            $this->owner = $row['table_owner'];
        }
        if (isset($row['date'])) {
            $this->date  = $row['date'];
        }
        if (isset($row['description'])) {
            $this->description = $row['description'];
        }
        if (isset($row['status'])) {
            $this->status = $row['status'];
        }
        if (isset($row['notification'])) {
            $this->notification = $row['notification'];
        }
        if (isset($row['notification_occurence'])) {
            $this->notificationOccurence = $row['notification_occurence'];
        }
        if (isset($row['might_be_corrupted'])) {
            $this->is_potentially_corrupted = $row['might_be_corrupted'];
        }
        $this->approvalState = $this->computeApprovalState($row);
    }

    /*static*/ function computeApprovalState($row)
    {
        $approvalState = null;
        if (isset($row['nb_reviewers']) && isset($row['rejected']) && isset($row['nb_approved']) && isset($row['nb_declined'])) {
            if ($row['rejected'] > 0) {
                $approvalState = PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED;
            } elseif ($row['nb_reviewers'] > 0 // There are reviewers
                     && $row['nb_approved'] > 0 // Avoid case when everybody "Will not review"
                     && (($row['nb_reviewers'] == $row['nb_approved']) // Everybody approved
                         || $row['nb_reviewers'] == ($row['nb_approved'] + $row['nb_declined']))
                     ) {
                    $approvalState = PLUGIN_DOCMAN_APPROVAL_STATE_APPROVED;
            } else {
                $approvalState = PLUGIN_DOCMAN_APPROVAL_STATE_NOTYET;
            }
        }
        return $approvalState;
    }

    // Convenient accessors
    function isDisabled()
    {
        if ($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED) {
            return true;
        }
        return false;
    }

    function isEnabled()
    {
        if ($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED) {
            return true;
        }
        return false;
    }

    function isClosed()
    {
        if ($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED) {
            return true;
        }
        return false;
    }

    function isCustomizable()
    {
        return $this->getCustomizable();
    }

    // Reviewers management
    // Should be managed with SplObjectStorage in Php 5
    function addReviewer($reviewer)
    {
        $this->reviewers[$reviewer->getId()] = $reviewer;
    }

    function getReviewer($id)
    {
        return $this->reviewers[$id];
    }

    function isReviewer($id)
    {
        return isset($this->reviewers[$id]);
    }

    function &getReviewerArray()
    {
        return $this->reviewers;
    }

    function getReviewerIterator()
    {
        return new ArrayIterator($this->reviewers);
    }
}
