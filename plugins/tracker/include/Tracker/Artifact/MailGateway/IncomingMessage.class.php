<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

class Tracker_Artifact_MailGateway_IncomingMessage
{

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /** @var  PFUser */
    private $user;

    /** @var Tracker */
    private $tracker;

    /** @var Tracker_Artifact */
    private $artifact;

    public function __construct(
        $subject,
        $body,
        PFUser $user,
        Tracker $tracker,
        ?Tracker_Artifact $artifact = null
    ) {
        $this->subject = $subject;
        $this->body = $body;
        $this->user = $user;
        $this->tracker = $tracker;
        $this->artifact = $artifact;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string The body of the message
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * @return Tracker_Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * @return bool
     */
    public function isAFollowUp()
    {
        return $this->artifact !== null;
    }
}
