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
    public $id;
    public $date;
    public $owner;
    public $description;
    public $status;
    public $notification;
    public $notificationOccurence;

    public $approvalState;
    public $customizable;
    public $reviewers;

    public function __construct()
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

    public function setId($v)
    {
        $this->id = $v;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setDate($v)
    {
        $this->date = $v;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setOwner($v)
    {
        $this->owner = $v;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setDescription($v)
    {
        $this->description = $v;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setStatus($v)
    {
        $this->status = $v;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setNotification($v)
    {
        $this->notification = $v;
    }

    public function getNotification()
    {
        return $this->notification;
    }

    public function setNotificationOccurence($v)
    {
        $this->notificationOccurence = $v;
    }

    public function getNotificationOccurence()
    {
        return $this->notificationOccurence;
    }

    public function setCustomizable($v)
    {
        $this->customizable = $v;
    }

    public function getCustomizable()
    {
        return $this->customizable;
    }

    public function getApprovalState()
    {
        return $this->approvalState;
    }

    public function initFromRow($row)
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
        $this->approvalState = $this->computeApprovalState($row);
    }

    /*static*/ public function computeApprovalState($row)
    {
        $approvalState = null;
        if (isset($row['nb_reviewers']) && isset($row['rejected']) && isset($row['nb_approved']) && isset($row['nb_declined'])) {
            if ($row['rejected'] > 0) {
                $approvalState = PLUGIN_DOCMAN_APPROVAL_STATE_REJECTED;
            } elseif (
                $row['nb_reviewers'] > 0 // There are reviewers
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
    public function isDisabled()
    {
        if ($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_DISABLED) {
            return true;
        }
        return false;
    }

    public function isEnabled()
    {
        if ($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_ENABLED) {
            return true;
        }
        return false;
    }

    public function isClosed()
    {
        if ($this->status == PLUGIN_DOCMAN_APPROVAL_TABLE_CLOSED) {
            return true;
        }
        return false;
    }

    public function isCustomizable()
    {
        return $this->getCustomizable();
    }

    // Reviewers management
    // Should be managed with SplObjectStorage in Php 5
    public function addReviewer($reviewer)
    {
        $this->reviewers[$reviewer->getId()] = $reviewer;
    }

    public function getReviewer($id)
    {
        return $this->reviewers[$id];
    }

    public function isReviewer($id)
    {
        return isset($this->reviewers[$id]);
    }

    public function &getReviewerArray()
    {
        return $this->reviewers;
    }

    public function getReviewerIterator()
    {
        return new ArrayIterator($this->reviewers);
    }
}
