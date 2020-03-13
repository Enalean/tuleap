<?php
/**
 * Copyright (c) Enalean, 2013 - 2016. All Rights Reserved.
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

/**
 * Represents an artifact or a specifict information of it
 */
abstract class Tracker_Artifact_View_View
{
    /** @var Tracker_Artifact */
    protected $artifact;

    /** @var PFUser */
    protected $user;

    /** @var Codendi_Request */
    protected $request;

    public function __construct(Tracker_Artifact $artifact, Codendi_Request $request, PFUser $user)
    {
        $this->artifact = $artifact;
        $this->request  = $request;
        $this->user     = $user;
    }

    /**
     * @return string url to reach the view
     */
    public function getURL()
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(
            array(
                'aid'  => $this->artifact->getId(),
                'view' => $this->getIdentifier()
            )
        );
    }

    /**
     * @return string
     */
    abstract public function getTitle();

    /**
     * @return string unique identifier
     */
    abstract public function getIdentifier();

    /**
     * @return string html
     */
    abstract public function fetch();
}
