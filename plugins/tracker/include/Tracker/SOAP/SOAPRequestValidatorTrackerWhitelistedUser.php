<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\SOAP;

use PFUser;
use Project;
use Tuleap\SOAP\SOAPRequestValidator;

class SOAPRequestValidatorTrackerWhitelistedUser implements SOAPRequestValidator
{
    /**
     * @var SOAPRequestValidator
     */
    private $request_validator;
    /**
     * @var array
     */
    private $whitelisted_usernames;

    public function __construct(SOAPRequestValidator $request_validator)
    {
        $this->request_validator = $request_validator;
        $whitelisted_users_setting = \ForgeConfig::get('soap_tracker_whitelisted_users');
        if ($whitelisted_users_setting === false) {
            $this->whitelisted_usernames = [];
        } else {
            $this->whitelisted_usernames = array_map('trim', explode(',', $whitelisted_users_setting));
        }
    }

    public function continueSession($session_key)
    {
        $user = $this->request_validator->continueSession($session_key);
        if (in_array($user->getUserName(), $this->whitelisted_usernames, true)) {
            return $user;
        }
        throw new NotTrackerWhitelistedUserException();
    }

    public function assertUserCanAccessProject(PFUser $user, Project $project)
    {
        $this->request_validator->assertUserCanAccessProject($user, $project);
    }

    public function getProjectById($project_id, $method_name)
    {
        return $this->request_validator->getProjectById($project_id, $method_name);
    }

    public function getProjectByName($project_name, $method_name)
    {
        return $this->request_validator->getProjectByName($project_name, $method_name);
    }
}
