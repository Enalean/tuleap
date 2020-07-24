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
namespace Tuleap\Tracker\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Tracker;
use Tuleap\REST\ProjectAuthorization;
use URLVerification;

/**
 * Check tracker permissions for a given user.
 */
class TrackerPermissionsChecker
{

    /**
     * @var URLVerification
     */
    private $url_verification;

    public function __construct(URLVerification $url_verification)
    {
        $this->url_verification = $url_verification;
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    public function checkRead(PFUser $user, Tracker $tracker)
    {
        ProjectAuthorization::userCanAccessProject(
            $user,
            $tracker->getProject(),
            $this->url_verification
        );

        if ($tracker->isDeleted()) {
            throw new RestException(
                404,
                null,
                ['i18n_error_message' => dgettext('tuleap-tracker', 'This tracker is deleted')]
            );
        }

        if (! $tracker->userCanView($user)) {
            throw new RestException(
                403,
                null,
                ['i18n_error_message' => dgettext('tuleap-tracker', 'You cannot access to this tracker')]
            );
        }
    }

    /**
     * @throws RestException 403
     * @throws RestException 404
     */
    public function checkUpdateWorkflow(PFUser $user, Tracker $tracker)
    {
        $this->checkRead($user, $tracker);

        if (! $tracker->userIsAdmin($user)) {
            throw new RestException(
                403,
                null,
                ['i18n_error_message' => dgettext('tuleap-tracker', 'You must be tracker administrator to access this resource.')]
            );
        }
    }
}
