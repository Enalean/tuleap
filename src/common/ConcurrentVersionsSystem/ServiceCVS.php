<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ConcurrentVersionsSystem;

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Project\Admin\Navigation\NavigationItemPresenter;

class ServiceCVS extends \Service
{
    public function displayCVSAdminHeader(\PFUser $user): void
    {
        $title = $GLOBALS['Language']->getText('cvs_admin_commit', 'title');
        $this->displayCVSHeader($user, $title, $title, [], []);
    }

    public function displayCVSRepositoryHeader(\PFUser $user, string $title, string $current_pane_shortname, array $params = []): void
    {
        $tabs = [
            new NavigationItemPresenter(
                $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_info'),
                '/cvs/?' . http_build_query(
                    [
                        'func'     => 'info',
                        'group_id' => $this->project->getID(),
                    ],
                ),
                'info',
                $current_pane_shortname
            )
        ];

        if ($this->project->isPublic() || user_isloggedin()) {
            $tabs[] = new NavigationItemPresenter(
                $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_browse'),
                '/cvs/viewvc.php/?' . http_build_query(
                    [
                        'root'     => $this->project->getUnixName(false),
                        'roottype' => 'cvs',
                    ],
                ),
                'browse',
                $current_pane_shortname
            );
        }

        if (user_isloggedin()) {
            $tabs[] = new NavigationItemPresenter(
                $GLOBALS['Language']->getText('cvs_commit_utils', 'menu_query'),
                '/cvs/?' . http_build_query(
                    [
                        'func'     => 'browse',
                        'group_id' => $this->project->getID(),
                    ],
                ),
                'query',
                $current_pane_shortname
            );
        }

        $this->displayCVSHeader($user, $title, $this->getInternationalizedName(), $tabs, $params);
    }

    private function displayCVSHeader(\PFUser $user, string $page_title, string $main_title, array $tabs, array $params): void
    {
        $additional_params = [
            'body_class' => [],
        ];
        if (isset($params['body_class'])) {
            $additional_params['body_class'] = $params['body_class'];
        }
        $additional_params['body_class'][] = 'cvs-body';

        $cvs_breadcrumb = new BreadCrumb(
            new BreadCrumbLink($this->getInternationalizedName(), $this->getUrl()),
        );

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($cvs_breadcrumb);

        if ($user->isAdmin($this->project->getID())) {
            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(
                new SubItemsUnlabelledSection(
                    new BreadCrumbLinkCollection(
                        [
                            new BreadCrumbLink(
                                _('Administration'),
                                '/cvs/?' . http_build_query(
                                    [
                                        'func'     => 'admin',
                                        'group_id' => $this->project->getID(),
                                    ]
                                ),
                            )]
                    )
                )
            );
            $cvs_breadcrumb->setSubItems($sub_items);
        }

        $this->displayHeader($page_title, $breadcrumbs, [], $additional_params);

        $renderer = \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates/project');

        $renderer->renderToPage('cvs-header', [
            'admin_section_title' => $main_title,
            'getEntries' => $tabs,
            'hasEntries' => ! empty($tabs),
        ]);
    }
}
