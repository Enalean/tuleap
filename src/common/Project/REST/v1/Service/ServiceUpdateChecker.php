<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST\v1\Service;

use Luracast\Restler\RestException;
use PFUser;
use Service;
use Tuleap\Project\Service\ServiceCannotBeUpdatedException;
use Tuleap\Project\ServiceCanBeUpdated;
use Tuleap\REST\I18NRestException;

final class ServiceUpdateChecker
{
    public function __construct(private readonly ServiceCanBeUpdated $service_manager)
    {
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function checkServiceCanBeUpdated(ServicePUTRepresentation $body, Service $service, PFUser $user): void
    {
        try {
            $this->service_manager->checkServiceCanBeUpdated(
                $service->getProject(),
                $service->getShortName(),
                $body->is_enabled,
                $user
            );
        } catch (ServiceCannotBeUpdatedException $exception) {
            throw new RestException(400, $exception->getMessage());
        }

        if ($body->is_enabled && ! $service->isActive()) {
            throw new I18NRestException(400, _('Inactive service cannot be enabled.'));
        }
    }
}
