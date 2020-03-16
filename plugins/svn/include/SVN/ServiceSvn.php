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
        $params = array(
            'body_class' => array($body_class)
        );
        $GLOBALS['HTML']->includeJavascriptSnippet(
            file_get_contents($GLOBALS['Language']->getContent('script_locale', null, 'svn', '.js'))
        );
        $toolbar = array();
        if ($this->getPermissionsManager()->isAdmin($request->getProject(), $request->getCurrentUser())) {
            $toolbar[] = [
                'title'     => "Administration",
                'url'       => SVN_BASE_URL . "/?group_id=" . urlencode((string) $request->getProject()->getId()) .
                    "&action=admin-groups",
                'data-test' => 'svn-admin-groups'
            ];
        }
        $title       = $title . ' - ' . dgettext('tuleap-svn', 'SVN');
        $breadcrumbs = array(
            array(
                'title' => "Repository List",
                'url'   => SVN_BASE_URL . "/?group_id=" . $request->getProject()->getId()
            )
        );
        $this->displayHeader($title, $breadcrumbs, $toolbar, $params);
    }

    public static function getDefaultServiceData($project_id)
    {
        return array(
            'label'        => 'plugin_svn:service_lbl_key',
            'description'  => 'plugin_svn:service_desc_key',
            'link'         => "/plugins/svn/?group_id=$project_id",
            'short_name'   => SvnPlugin::SERVICE_SHORTNAME,
            'scope'        => 'system',
            'rank'         => 136,
            'location'     => 'master',
            'is_in_iframe' => 0,
            'server_id'    => 0,
        );
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
