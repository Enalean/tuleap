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

namespace Tuleap\Kanban\Service;

use Tuleap\Kanban\KanbanDao;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\SidebarPromotedItemPresenter;
use Tuleap\Project\Service\ServiceForCreation;

final class KanbanService extends \Service implements ServiceForCreation
{
    private const ICON_NAME           = 'fa-solid fa-tlp-kanban-boards';
    public const SERVICE_SHORTNAME    = 'plugin_kanban';
    public const INSTRUMENTATION_NAME = 'kanban';


    public static function forServiceCreation(\Project $project): self
    {
        return new self(
            $project,
            [
                'service_id' => self::FAKE_ID_FOR_CREATION,
                'group_id' => $project->getID(),
                'label' => 'label',
                'description' => '',
                'short_name' => self::SERVICE_SHORTNAME,
                'link' => null,
                'is_active' => 1,
                'is_used' => 0,
                'scope' => self::SCOPE_SYSTEM,
                'rank' => 154,
                'is_in_iframe' => 0,
                'is_in_new_tab' => false,
            ]
        );
    }

    public function getIconName(): string
    {
        return self::ICON_NAME;
    }

    public function getInternationalizedName(): string
    {
        return dgettext('tuleap-kanban', 'Kanban');
    }

    public function getProjectAdministrationName(): string
    {
        return dgettext('tuleap-kanban', 'Kanban');
    }

    public function getInternationalizedDescription(): string
    {
        return dgettext('tuleap-kanban', 'Kanban boards');
    }

    public function urlCanChange(): bool
    {
        return false;
    }

    public function displayKanbanHeader(): void
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-kanban', 'Kanban'),
                KanbanServiceHomepageUrlBuilder::getUrl($this->project),
            )
        ));

        $this->displayHeader(
            dgettext('tuleap-kanban', 'Kanban'),
            $breadcrumbs,
            []
        );
    }

    public function getUrl(?string $url = null): string
    {
        return KanbanServiceHomepageUrlBuilder::getUrl($this->project);
    }

    /**
     * @return list<SidebarPromotedItemPresenter>
     */
    public function getPromotedItemPresenters(\PFUser $user, ?string $active_promoted_item_id): array
    {
        $kanban_factory = new KanbanFactory(
            \TrackerFactory::instance(),
            new KanbanDao(),
        );

        $kanban_presenters = [];

        $list_of_kanban = $kanban_factory->getListOfKanbansForProject(
            $user,
            (int) $this->project->getID(),
        );

        foreach ($list_of_kanban as $kanban_for_project) {
            if (! $kanban_for_project->is_promoted) {
                continue;
            }

            $kanban_presenters[] = new SidebarPromotedItemPresenter(
                '/kanban/' . urlencode((string) $kanban_for_project->getId()),
                $kanban_for_project->getName(),
                $kanban_for_project->tracker->getDescription(),
                $kanban_for_project->getPromotedKanbanId() === $active_promoted_item_id,
                null,
            );
        }

        return $kanban_presenters;
    }
}
