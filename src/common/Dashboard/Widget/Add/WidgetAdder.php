<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Widget\Add;

use ProjectHistoryDao;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\WidgetCreator;
use Tuleap\Dashboard\Widget\WidgetProjectAdminActionsHistoryEntry;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Widget\WidgetFactory;
use Widget;

final readonly class WidgetAdder
{
    public function __construct(
        private DashboardWidgetDao $dao,
        private WidgetFactory $factory,
        private WidgetCreator $creator,
        private DisabledProjectWidgetsChecker $disabled_project_widgets_checker,
        private ProjectHistoryDao $history_dao,
        private ProjectByIDFactory $project_factory,
    ) {
    }

    /**
     * @return Ok<Widget>|Err<Fault>
     */
    public function add(
        \PFUser $current_user,
        int $owner_id,
        string $dashboard_type,
        int $dashboard_id,
        string $name,
        \Codendi_Request $request_to_configure_widget,
    ): Ok|Err {
        try {
            $this->checkThatDashboardBelongsToTheOwner($owner_id, $dashboard_type, $dashboard_id);
            $widget = $this->factory->getInstanceByWidgetName($name);
            if ($widget === null) {
                return Result::err(UnableToInstantiateWidgetFault::build());
            }

            if ($this->disabled_project_widgets_checker->isWidgetDisabled($widget, $dashboard_type) === true) {
                return Result::err(WidgetDisabledInDashboardFault::build());
            }

            if (! $widget->isUnique() || ! $this->isUniqueWidgetAlreadyAddedInDashboard($widget, $dashboard_id, $dashboard_type)) {
                $content_id         = $this->creator->create(
                    $owner_id,
                    $this->factory->getOwnerTypeByDashboardType($dashboard_type),
                    $dashboard_id,
                    $widget,
                    $request_to_configure_widget
                );
                $widget->owner_id   = $owner_id;
                $widget->owner_type = $dashboard_type;
                $widget->loadContent($content_id);

                if ($dashboard_type === ProjectDashboardController::DASHBOARD_TYPE) {
                    $this->history_dao->addHistory(
                        $this->project_factory->getProjectById($owner_id),
                        $current_user,
                        new \DateTimeImmutable(),
                        WidgetProjectAdminActionsHistoryEntry::AddWidget->value,
                        $name,
                        [],
                    );
                }

                return Result::ok($widget);
            } else {
                return Result::err(AlreadyUsedWidgetFault::build());
            }
        } catch (\Exception $exception) {
            if ($exception->getMessage()) {
                return Result::err(Fault::fromThrowable($exception));
            } else {
                return Result::err(ErrorAddingWidgetFault::build());
            }
        }
    }

    private function isUniqueWidgetAlreadyAddedInDashboard(Widget $widget, int $dashboard_id, string $dashboard_type): bool
    {
        $used_widgets = $this->getUsedWidgets($dashboard_id, $dashboard_type);

        return $widget->isUnique() && in_array($widget->getId(), $used_widgets);
    }

    /**
     * @return list<string>
     */
    private function getUsedWidgets(int $dashboard_id, string $dashboard_type): array
    {
        $used_widgets = [];
        foreach ($this->dao->searchUsedWidgetsContentByDashboardId($dashboard_id, $dashboard_type) as $row) {
            $used_widgets[] = $row['name'];
        }

        return $used_widgets;
    }

    private function checkThatDashboardBelongsToTheOwner(int $owner_id, string $dashboard_type, int $dashboard_id): void
    {
        $this->dao->checkThatDashboardBelongsToTheOwner(
            $owner_id,
            $dashboard_type,
            $dashboard_id
        );
    }
}
