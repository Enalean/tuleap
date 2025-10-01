<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Service;

final class ServiceAvailabilityHandler
{
    private const string LEGACY_MEDIAWIKI_SERVICE_SHORTNAME = 'plugin_mediawiki';

    public function __construct(private MediawikiFlavorUsage $mediawiki_flavor_usage)
    {
    }

    public function handle(ServiceAvailability $service_availability): void
    {
        $project = $service_availability->getProject();
        if ($service_availability->isForService(MediawikiStandaloneService::SERVICE_SHORTNAME)) {
            if ($project->usesService(self::LEGACY_MEDIAWIKI_SERVICE_SHORTNAME)) {
                $service_availability->cannotBeActivated(dgettext('tuleap-mediawiki_standalone', 'The MediaWiki standalone service cannot activated when the Mediawiki service is active'));
                return;
            }
            if ($this->mediawiki_flavor_usage->wasLegacyMediawikiUsed($project)) {
                $service_availability->cannotBeActivated(dgettext('tuleap-mediawiki_standalone', 'The MediaWiki standalone service cannot activated when there are still legacy MediaWiki data'));
                return;
            }
        }

        if ($service_availability->isForService(self::LEGACY_MEDIAWIKI_SERVICE_SHORTNAME)) {
            if ($project->usesService(MediawikiStandaloneService::SERVICE_SHORTNAME)) {
                $service_availability->cannotBeActivated(dgettext('tuleap-mediawiki_standalone', 'The Mediawiki service cannot activated when the MediaWiki standalone service is active'));
                return;
            }

            if ($this->mediawiki_flavor_usage->wasStandaloneMediawikiUsed($project)) {
                $service_availability->cannotBeActivated(dgettext('tuleap-mediawiki_standalone', 'The Mediawiki service cannot activated when there are still MediaWiki standalone data'));
            }
        }
    }
}
