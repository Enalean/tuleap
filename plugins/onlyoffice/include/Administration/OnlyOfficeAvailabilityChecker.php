<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OnlyOffice\Administration;

use Psr\Log\LoggerInterface;
use Tuleap\OnlyOffice\DocumentServer\IRetrieveDocumentServers;

final class OnlyOfficeAvailabilityChecker implements CheckOnlyOfficeIsAvailable
{
    public function __construct(
        private LoggerInterface $logger,
        private IRetrieveDocumentServers $servers_retriever,
    ) {
    }

    #[\Override]
    public function isOnlyOfficeIntegrationAvailableForProject(\Project $project): bool
    {
        $servers                     = $this->servers_retriever->retrieveAll();
        $server_with_existing_secret = false;
        foreach ($servers as $server) {
            if ($server->url && $server->has_existing_secret) {
                $server_with_existing_secret = true;
            }
        }

        if (! $server_with_existing_secret) {
            $this->logger->debug('No document server with existing secret key has been defined');

            return false;
        }

        if (! $project->usesService(\DocmanPlugin::SERVICE_SHORTNAME)) {
            return false;
        }

        foreach ($servers as $server) {
            if ($server->isProjectAllowed($project)) {
                return true;
            }
        }

        return false;
    }
}
