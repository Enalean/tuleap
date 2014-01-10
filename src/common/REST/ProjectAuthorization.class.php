<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\REST;

use \PFUser;
use \Project;
use \URLVerification;
use \Luracast\Restler\RestException;
use \Project_AccessProjectNotFoundException;
use \Project_AccessException;

class ProjectAuthorization {

    public static function userCanAccessProject(PFUser $user, Project $project) {
         try {
            $url_verification = new URLVerification();
            $url_verification->userCanAccessProject($user, $project);
            return true;
        } catch (Project_AccessProjectNotFoundException $exception) {
            throw new RestException(404);
        } catch (Project_AccessException $exception) {
            throw new RestException(403, $exception->getMessage());
        }
    }
}
