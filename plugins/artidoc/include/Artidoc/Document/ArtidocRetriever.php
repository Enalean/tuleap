<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document;

use DocmanPlugin;
use Project_NotFoundException;
use ServiceTracker;
use trackerPlugin;
use Tuleap\Docman\Item\GetItemFromRow;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Project\ProjectByIDFactory;

final class ArtidocRetriever implements RetrieveArtidoc
{
    public function __construct(
        private ProjectByIDFactory $project_manager,
        private SearchArtidocDocument $dao,
        private GetItemFromRow $item_factory,
        private IsProjectAllowedToUsePlugin $plugin,
    ) {
    }

    public function retrieveArtidoc(int $id, \PFUser $user): Ok|Err
    {
        $row = $this->dao->searchById($id);
        if (! $row) {
            return Result::err(Fault::fromMessage('Unable to find document'));
        }

        $item = $this->item_factory->getItemFromRow($row);
        if (! $item instanceof ArtidocDocument) {
            return Result::err(Fault::fromMessage('Item is not an artidoc document'));
        }

        $permissions_manager = \Docman_PermissionsManager::instance((int) $item->getGroupId());
        if (! $permissions_manager->userCanRead($user, (int) $item->getId())) {
            return Result::err(Fault::fromMessage('User cannot read document'));
        }

        try {
            $project = $this->project_manager->getValidProjectById($item->getGroupId());
        } catch (Project_NotFoundException $e) {
            return Result::err(Fault::fromThrowableWithMessage($e, 'Project is not valid'));
        }

        if (! $this->plugin->isAllowed((int) $project->getID())) {
            return Result::err(Fault::fromMessage('Project is not allowed to use artidoc'));
        }


        if (! $project->getService(trackerPlugin::SERVICE_SHORTNAME) instanceof ServiceTracker) {
            return Result::err(Fault::fromMessage('Project does not have tracker service enabled'));
        }

        $service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
        if (! $service instanceof ServiceDocman) {
            return Result::err(Fault::fromMessage('Project does not have docman service enabled'));
        }

        return Result::ok(new ArtidocDocumentInformation($item, $service));
    }
}
