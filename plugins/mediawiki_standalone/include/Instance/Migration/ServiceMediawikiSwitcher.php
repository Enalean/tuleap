<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Psr\Log\LoggerInterface;
use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;

final class ServiceMediawikiSwitcher implements SwitchMediawikiService
{
    public function __construct(private readonly \ServiceDao $service_dao, private readonly LoggerInterface $logger)
    {
    }

    public function switchToStandalone(\Project $project): void
    {
        $results = $this->service_dao->searchByProjectIdAndShortNames(
            (int) $project->getID(),
            [MigrateInstance::MEDIAWIKI_123_SERVICE_NAME],
        );
        $legacy  = null;
        if (count($results) > 0) {
            $legacy = $results->current();

            $this->logger->info('Deactivating legacy MediaWiki service');
            $this->service_dao->updateServiceUsageByShortName(
                (int) $project->getID(),
                MigrateInstance::MEDIAWIKI_123_SERVICE_NAME,
                0,
            );
        }

        $results = $this->service_dao->searchByProjectIdAndShortNames(
            (int) $project->getID(),
            [MediawikiStandaloneService::SERVICE_SHORTNAME],
        );
        if (count($results) > 0) {
            $standalone = $results->current();
            if ($legacy) {
                $this->logger->info('Adjusting rank of MediaWiki Standalone service to map the one of legacy MediaWiki service');
                $this->service_dao->saveBasicInformation(
                    $standalone['service_id'],
                    $standalone['label'],
                    $standalone['icon'],
                    $standalone['description'],
                    $standalone['link'],
                    $legacy['rank'],
                    $standalone['is_in_iframe'],
                    $standalone['is_in_new_tab'],
                );
            }
            $this->logger->info('Activating MediaWiki Standalone service');
            $this->service_dao->updateServiceUsageByServiceID((int) $project->getID(), $standalone['service_id'], 1);
        } else {
            $this->logger->info('Creating MediaWiki Standalone service');
            $this->service_dao->create(
                (int) $project->getID(),
                'label',
                MediawikiStandaloneService::ICON_NAME,
                '',
                'plugin_mediawiki_standalone',
                null,
                1,
                1,
                'system',
                $legacy ? $legacy['rank'] : 161,
                false,
            );
        }
    }
}
