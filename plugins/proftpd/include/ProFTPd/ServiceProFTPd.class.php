<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class ServiceProFTPd extends Service
{

    public function getIconName(): string
    {
        return 'fa-tlp-folder-globe';
    }

    public function renderInPage(HTTPRequest $request, $title, $template, $presenter = null)
    {
        $this->displayServiceHeader($request, $title);

        if ($presenter) {
            $this->getRenderer()->renderToPage($template, $presenter);
        }

        $this->displayFooter();
        exit;
    }

    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(PROFTPD_BASE_DIR) . '/templates');
    }

    private function displayServiceHeader(HTTPRequest $request, $title)
    {
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
                            )]
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
