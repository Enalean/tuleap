<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd;

use Service;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class ServiceProFTPd extends Service
{
    #[FeatureFlagConfigKey("Feature flag to allow users to use the proftpd UI that will be removed soon. Comma separated list of project ids. Please warn us if you activate this flag.")]
    public const FEATURE_FLAG_PROFTPD = 'allow_temporary_proftpd_ui_that_will_be_removed_soon';

    public function getIconName(): string
    {
        return 'fas fa-tlp-folder-globe';
    }

    public function renderInPage(HTTPRequest $request, $title, $template, $presenter = null)
    {
        $this->displayServiceHeader($request, $title);

        $comma_separated_project_ids = \ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_PROFTPD);
        if ($presenter && $comma_separated_project_ids) {
            $allowed_project_ids = explode(',', $comma_separated_project_ids);

            if (in_array((string) $request->getProject()->getID(), $allowed_project_ids, true)) {
                $this->getRenderer()->renderToPage($template, $presenter);
                $this->displayFooter();
                exit;
            }
        }

        $this->getRenderer()->renderToPage("error", []);
        $this->displayFooter();
        exit;
    }

    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(PROFTPD_BASE_DIR) . '/templates');
    }

    private function displayServiceHeader(HTTPRequest $request, $title)
    {
        $GLOBALS['HTML']->addFeedback(
            \Feedback::WARN,
            dgettext(
                'tuleap-proftpd',
                '(S)FTP Browser (and underlying server ProFTPD) is unsupported and will be removed soon. Please use Documents and/or Files services instead.'
            )
        );
        $proftpd_breadcrumb = new BreadCrumb(
            new BreadCrumbLink($this->getInternationalizedName(), $this->getUrl()),
        );

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($proftpd_breadcrumb);

        if ($this->userIsAdmin($request)) {
            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(
                new SubItemsUnlabelledSection(
                    new BreadCrumbLinkCollection(
                        [
                            new BreadCrumbLink(
                                _('Administration'),
                                PROFTPD_BASE_URL . '/?' . http_build_query([
                                    'group_id'   => $request->get('group_id'),
                                    'controller' => 'admin',
                                    'action'     => 'index',
                                ]),
                            ),
                        ]
                    )
                )
            );
            $proftpd_breadcrumb->setSubItems($sub_items);
        }

        $title .= ' - ' . $this->getInternationalizedName();
        $this->displayHeader($title, $breadcrumbs, []);
    }

    /**
     * @return bool
     */
    private function userIsAdmin(HTTPRequest $request)
    {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }
}
