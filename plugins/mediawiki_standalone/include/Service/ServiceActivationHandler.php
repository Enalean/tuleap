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

final class ServiceActivationHandler
{
    private const LEGACY_MEDIAWIKI_SERVICE_SHORTNAME = 'plugin_mediawiki';

    public function handle(ServiceActivation $service_activation): void
    {
        $project = $service_activation->getProject();
        if ($service_activation->isForService(\mediawiki_standalonePlugin::SERVICE_SHORTNAME) && $project->usesService(self::LEGACY_MEDIAWIKI_SERVICE_SHORTNAME)) {
            $service_activation->cannotBeActivated(dgettext('tuleap-mediawiki_standalone', 'The MediaWiki standalone service cannot activated when the Mediawiki service is active'));
            return;
        }

        if ($service_activation->isForService(self::LEGACY_MEDIAWIKI_SERVICE_SHORTNAME) && $project->usesService(\mediawiki_standalonePlugin::SERVICE_SHORTNAME)) {
            $service_activation->cannotBeActivated(dgettext('tuleap-mediawiki_standalone', 'The Mediawiki service cannot activated when the MediaWiki standalone service is active'));
        }
    }
}
