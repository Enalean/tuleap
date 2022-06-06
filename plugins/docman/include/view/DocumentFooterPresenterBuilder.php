<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\view;

use PFUser;
use Tuleap\Docman\DocumentFooterPresenter;
use Tuleap\Docman\ExternalLinks\ExternalLinksManager;
use Tuleap\Docman\ExternalLinks\Link;

class DocumentFooterPresenterBuilder
{
    public function __construct(private \ProjectManager $project_manager)
    {
    }

    public function build(
        array $params,
        int $project_id,
        array $item,
        PFUser $user,
    ): DocumentFooterPresenter {
        $is_folder_in_migrated_view = $this->isFolderInMigratedView($params, $item);
        $folder_id                  = $this->getFolderId($is_folder_in_migrated_view, $item);

        $project   = $this->project_manager->getProject($project_id);
        $collector = new ExternalLinksManager();
        if ($is_folder_in_migrated_view === true && ! $user->isAnonymous()) {
            $collector->addExternalLink(new Link($project, $folder_id));
        }


        return new DocumentFooterPresenter($project, $collector);
    }

    private function getFolderId(bool $is_folder_in_migrated_view, array $item): int
    {
        if ($is_folder_in_migrated_view && $item['parent_id'] !== 0) {
            return $item['item_id'];
        }
        return 0;
    }

    private function isFolderInMigratedView(array $params, array $item): bool
    {
        return $item['item_type'] === PLUGIN_DOCMAN_ITEM_TYPE_FOLDER && isset($params['action']) && $params['action']
            === "show";
    }
}
