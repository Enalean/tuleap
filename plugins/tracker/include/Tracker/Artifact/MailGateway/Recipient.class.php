<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

/**
 * Value object of a the recipient of email gateway
 */
class Tracker_Artifact_MailGateway_Recipient
{

    /** @var string */
    private $email;

    /** @var PFUser */
    private $user;

    /** @var Artifact */
    private $artifact;

    public function __construct(
        PFUser $user,
        Artifact $artifact,
        $email
    ) {
        $this->user     = $user;
        $this->artifact = $artifact;
        $this->email    = $email;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getArtifact()
    {
        return $this->artifact;
    }

    public function getUser()
    {
        return $this->user;
    }
}
