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

namespace Tuleap\Timetracking\Widget;

use Codendi_Request;
use TemplateRendererFactory;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\Dao;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\PredefinedTimePeriod;
use Tuleap\Timetracking\Widget\Management\TimetrackingManagementWidgetConfig;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;
use UserManager;
use Widget;

class TimetrackingManagementWidget extends Widget
{
    public const NAME = 'timetracking-management-widget';

    private Dao $dao;

    public function __construct(Dao $dao)
    {
        parent::__construct(self::NAME);
        $this->dao = $dao;
    }

    public function getTitle(): string
    {
        return dgettext('tuleap-timetracking', 'Timetracking management');
    }

    public function getDescription(): string
    {
        return dgettext('tuleap-timetracking', 'Displays aggregated time per user over a given period, with a view of time spent on each project.');
    }

    public function isUnique(): true
    {
        return true;
    }

    /**
     * @param string $widget_id
     */
    public function hasPreferences($widget_id): false
    {
        return false;
    }

    public function getCategory(): string
    {
        return dgettext('tuleap-timetracking', 'Time tracking');
    }

    public function getContent(): string
    {
        $widget_config = TimetrackingManagementWidgetConfig::fromWidgetId(
            $this->dao,
            $this->dao,
            UserManager::instance(),
            new UserAvatarUrlProvider(
                new AvatarHashDao(),
                new ComputeAvatarHash()
            ),
            (int) $this->content_id,
        );
        $renderer      = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);

        return $renderer->renderToString('timetracking-management', [
            'widget_id' => $this->content_id,
            'widget_config' => json_encode($widget_config),
        ]);
    }

    public function getIcon(): string
    {
        return 'fa-clock-o';
    }

    /**
     * @return JavascriptAssetGeneric[]
     */
    public function getJavascriptAssets(): array
    {
        return [
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/timetracking-management-widget/frontend-assets',
                    '/assets/timetracking/timetracking-management-widget'
                ),
                'src/index.ts'
            ),
        ];
    }

    public function create(Codendi_Request $request): int
    {
        return $this->dao->create(PredefinedTimePeriod::LAST_7_DAYS);
    }

    /**
     * @param string $id
     */
    public function destroy($id): void
    {
        $this->dao->delete((int) $id);
    }

    /**
     * @param string $id
     */
    public function loadContent($id): void
    {
        $this->content_id = $id;
    }
}
