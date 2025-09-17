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

namespace Tuleap\Document\RecentlyVisited;

use Docman_ItemFactory;
use Docman_PermissionsManager;
use Tuleap\Docman\Reference\DocumentIconPresenterBuilder;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\Registration\Template\Upload\Tus\ProjectNotFoundException;
use Tuleap\QuickLink\SwitchToQuickLink;
use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryEntryCollection;

final readonly class VisitedDocumentRetriever
{
    public const string TYPE = 'document';

    public function __construct(
        private RecentlyVisitedDocumentDao $dao,
        private ProjectByIDFactory $project_factory,
        private DocumentIconPresenterBuilder $icon_presenter_builder,
        private VisitedDocumentHrefVisitor $href_visitor,
    ) {
    }

    public function getVisitHistory(HistoryEntryCollection $collection, int $max_length_history): void
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId(
            (int) $collection->getUser()->getId(),
            $max_length_history
        );

        foreach ($recently_visited_rows as $recently_visited_row) {
            $this->addEntry(
                $collection,
                $recently_visited_row['created_on'],
                $recently_visited_row['project_id'],
                $recently_visited_row['item_id']
            );
        }
    }

    private function addEntry(
        HistoryEntryCollection $collection,
        int $created_on,
        int $project_id,
        int $item_id,
    ): void {
        try {
            $project = $this->project_factory->getValidProjectById($project_id);
        } catch (ProjectNotFoundException) {
            return;
        }

        $item_factory = $this->getItemFactory($project_id);

        $item = $item_factory->getItemFromDb($item_id);
        if (! $item) {
            return;
        }

        $permissions_manager = $this->getPermissionManager($project_id);
        if (! $permissions_manager->userCanAccess($collection->getUser(), $item->getId())) {
            return;
        }

        $icon_presenter = $this->icon_presenter_builder->buildForItem($item);

        $collection->addEntry(
            new HistoryEntry(
                $created_on,
                null,
                $item->accept($this->href_visitor, ['project' => $project]),
                $item->getTitle(),
                $icon_presenter->color,
                self::TYPE,
                (int) $item->getId(),
                null,
                null,
                $icon_presenter->icon,
                $project,
                [
                    new SwitchToQuickLink(
                        dgettext('tuleap-document', 'Open the Quick look view of the document'),
                        '/plugins/document/'
                            . urlencode($project->getUnixNameMixedCase())
                            . '/preview/'
                            . urlencode((string) $item->getId()),
                        'fa-solid fa-eye',
                    ),
                ],
                [],
            )
        );
    }

    private function getItemFactory(int $project_id): Docman_ItemFactory
    {
        return new Docman_ItemFactory($project_id);
    }

    private function getPermissionManager(int $project_id): Docman_PermissionsManager
    {
        return Docman_PermissionsManager::instance($project_id);
    }
}
