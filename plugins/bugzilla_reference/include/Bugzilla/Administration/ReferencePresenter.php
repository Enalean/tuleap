<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Bugzilla\Administration;

class ReferencePresenter
{
    private $are_followup_private;
    private $keyword;
    private $server;
    private $username;
    private $api_key;
    private $id;
    private $rest_url;

    public function __construct($id, $reference, $server, $username, $api_key, $are_followup_private, $rest_url)
    {
        $this->id                   = $id;
        $this->keyword              = $reference;
        $this->server               = $server;
        $this->username             = $username;
        $this->api_key              = $api_key;
        $this->are_followup_private = $are_followup_private;
        $this->rest_url             = $rest_url;
    }

    public function getKeyword()
    {
        return $this->keyword;
    }

    public function getServer()
    {
        return $this->server;
    }

    public function getFollowUpVisibility()
    {
        if ($this->are_followup_private) {
            return dgettext('tuleap-bugzilla_reference', 'Private');
        }

        return dgettext('tuleap-bugzilla_reference', 'Public');
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function areFollowUpPrivate()
    {
        return $this->are_followup_private;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isApiKeySet()
    {
        return $this->api_key !== "";
    }

    public function getRestUrl()
    {
        return $this->rest_url;
    }
}
