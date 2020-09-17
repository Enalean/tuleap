<?php
/**
 * Copyright (c) Enalean, 2015-2020. All Rights Reserved.
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

namespace Tuleap\SVN;

use HTTPRequest;
use PermissionsManager;
use Service;
use SvnPlugin;
use TemplateRendererFactory;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class ServiceSvn extends Service
{

    /** @var SvnPermissionManager */
    private $permissions_manager;

    public function __construct($project, $data)
    {
        parent::__construct($project, $data);
        $this->permissions_manager = null;
    }

    public function getIconName(): string
    {
        return 'fa-tlp-versioning-svn';
    }

    private function getPermissionsManager()
    {
        if (empty($this->permissions_manager)) {
            $this->permissions_manager = new SvnPermissionManager(
                PermissionsManager::instance()
            );
        }
        return $this->permissions_manager;
    }

    public function renderInPage(HTTPRequest $request, $title, $template, $presenter)
    {
        $body_class = '';
        $this->renderInPageWithBodyClass($request, $title, $template, $presenter, $body_class);
    }

    public function renderInPageWithBodyClass(HTTPRequest $request, $title, $template, $presenter, $body_class)
    {
        $this->displaySVNHeader($request, $title, $body_class);
        $this->getRenderer()->renderToPage($template, $presenter);
        $this->displayFooter();
        exit;
    }

    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(SVN_BASE_DIR) . '/templates');
    }

    private function displaySVNHeader(HTTPRequest $request, $title, $body_class): void
    {
        $params = [
            'body_class' => [$body_class]
        ];
        $GLOBALS['HTML']->includeJavascriptSnippet(
            file_get_contents($GLOBALS['Language']->getContent('script_locale', null, 'svn', '.js'))
        );
        $title = $title . ' - ' . dgettext('tuleap-svn', 'SVN');

        $repository_list_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext("tuleap-svn", "Repository list"),
                SVN_BASE_URL . "/?group_id=" . $request->getProject()->getId()
            )
        );
        if ($this->getPermissionsManager()->isAdmin($request->getProject(), $request->getCurrentUser())) {
            $admin_link = new BreadCrumbLink(
                _('Administration'),
                SVN_BASE_URL . "/?group_id=" . urlencode((string) $request->getProject()->getId()) . "&action=admin-groups",
            );
            $admin_link->setDataAttribute('test', 'svn-admin-groups');

            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection([$admin_link])));

            $repository_list_breadcrumb->setSubItems($sub_items);
        }

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($repository_list_breadcrumb);
        $this->displayHeader($title, $breadcrumbs, [], $params);
    }

    public static function getDefaultServiceData($project_id)
    {
        return [
            'label'        => 'plugin_svn:service_lbl_key',
            'description'  => 'plugin_svn:service_desc_key',
            'link'         => "/plugins/svn/?group_id=$project_id",
            'short_name'   => SvnPlugin::SERVICE_SHORTNAME,
            'scope'        => 'system',
            'rank'         => 136,
            'location'     => 'master',
            'is_in_iframe' => 0,
            'server_id'    => 0,
        ];
    }

    public function getInternationalizedName(): string
    {
        $label = $this->getLabel();

        if ($label === 'plugin_svn:service_lbl_key') {
            return dgettext('tuleap-svn', 'SVN');
        }

        return $label;
    }

    public function getInternationalizedDescription(): string
    {
        $description = $this->getDescription();

        if ($description === 'plugin_svn:service_desc_key') {
            return dgettext('tuleap-svn', 'SVN plugin to manage multiple SVN repositories');
        }

        return $description;
    }
}
