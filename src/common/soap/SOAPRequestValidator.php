<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\SOAP;

use PFUser;
use Project;

/**
 * This class is a convenient proxy for parameters checking when doing SOAP server
 *
 * It's meant to assert that given parameters are valid and returns corresponding
 * objects.
 * If there are errors, exception are thrown.
 *
 * It manipulates:
 * - user session
 * - project
 */
interface SOAPRequestValidator
{
    /**
     * @see session_continue
     */
    public function continueSession(string $session_key): PFUser;

    public function assertUserCanAccessProject(PFUser $user, Project $project): void;

    public function getProjectById($project_id, $method_name);

    public function getProjectByName($project_name, $method_name);
}
