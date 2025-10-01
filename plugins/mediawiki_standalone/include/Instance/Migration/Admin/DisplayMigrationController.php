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

namespace Tuleap\MediawikiStandalone\Instance\Migration\Admin;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationStatus;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class DisplayMigrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const string URL = '/mediawiki_standalone/admin/migrations';

    public function __construct(
        private readonly LegacyReadyToMigrateDao $to_migrate_dao,
        private readonly IsProjectAllowedToUsePlugin $plugin,
        private readonly AdminPageRenderer $renderer,
        private readonly CSRFSynchronizerTokenProvider $token_provider,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        if (! $current_user->isSuperUser()) {
            throw new NotFoundException();
        }

        $this->renderer->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeViteAssets(__DIR__ . '/../../../../scripts/siteadmin/frontend-assets', '/assets/mediawiki_standalone/siteadmin'),
                'src/index.ts'
            )
        );

        $this->renderer->renderAPresenter(
            dgettext('tuleap-mediawiki_standalone', 'Migrations to MediaWiki standalone'),
            __DIR__ . '/../../../../templates/siteadmin/',
            'legacy-migration',
            [
                'projects' => $this->getProjects($current_user),
                'token'    => CSRFSynchronizerTokenPresenter::fromToken($this->token_provider->getCSRF()),
            ]
        );
    }

    /**
     * @return LegacyReadyToMigratePresenter[]
     */
    public function getProjects(\PFUser $current_user): array
    {
        return array_map(
            function (array $row) use ($current_user): LegacyReadyToMigratePresenter {
                if ($row['ongoing_initialization_error'] === null) {
                    $initialization_status = OngoingInitializationStatus::None;
                } else {
                    $initialization_status = $row['ongoing_initialization_error']
                        ? OngoingInitializationStatus::InError
                        : OngoingInitializationStatus::Ongoing;
                }

                $project    = new \Project($row);
                $is_allowed = $this->plugin->isAllowed((int) $project->getID());

                return LegacyReadyToMigratePresenter::fromProject(
                    $project,
                    $initialization_status,
                    $current_user,
                    $is_allowed,
                );
            },
            $this->to_migrate_dao->searchProjectsUsingLegacyMediaWiki(),
        );
    }
}
