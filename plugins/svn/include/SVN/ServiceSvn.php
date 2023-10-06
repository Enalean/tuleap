<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\SVN\AccessControl\AccessControlPresenter;
use Tuleap\SVN\Admin\BaseGlobalAdminPresenter;
use Tuleap\SVN\Admin\GlobalAdministratorsController;
use Tuleap\SVN\Admin\HooksConfigurationPresenter;
use Tuleap\SVN\Admin\ImmutableTagPresenter;
use Tuleap\SVN\Admin\MailNotificationPresenter;
use Tuleap\SVN\Admin\RepositoryDeletePresenter;
use Tuleap\SVN\Explorer\ExplorerPresenter;
use Tuleap\SVN\Explorer\RepositoryDisplayPresenter;
use Tuleap\SVN\Repository\Repository;

class ServiceSvn extends Service
{
    /** @var SvnPermissionManager */
    private $permissions_manager;

    public function __construct($project, $data)
    {
        parent::__construct($project, $data);
    }

    public function getIconName(): string
    {
        return 'fas fa-tlp-versioning-svn';
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

    /**
     * @param BaseGlobalAdminPresenter|ExplorerPresenter $presenter
     */
    public function renderInPage(HTTPRequest $request, string $title, string $template, $presenter)
    {
        $body_class  = '';
        $breadcrumbs = new BreadCrumbCollection();
        $this->renderInPageWithBodyClass($request, $title, $template, $presenter, $body_class, $breadcrumbs);
    }

    /**
     * @param AccessControlPresenter|MailNotificationPresenter|HooksConfigurationPresenter|RepositoryDeletePresenter|ImmutableTagPresenter $presenter
     */
    public function renderInPageRepositoryAdministration(
        HTTPRequest $request,
        string $title,
        string $template,
        $presenter,
        string $body_class,
        Repository $repository,
    ): void {
        $breadcrumbs = new BreadCrumbCollection();
        $admin_crumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-svn', 'Settings'),
                $repository->getSettingUrl(),
            ),
        );
        $breadcrumbs->addBreadCrumb($admin_crumb);
        $this->renderInPageRepository($request, $title, $template, $presenter, $body_class, $repository, $breadcrumbs);
    }

    /**
     * @param AccessControlPresenter|MailNotificationPresenter|HooksConfigurationPresenter|RepositoryDeletePresenter|ImmutableTagPresenter|RepositoryDisplayPresenter $presenter
     */
    public function renderInPageRepository(
        HTTPRequest $request,
        string $title,
        string $template,
        $presenter,
        string $body_class,
        Repository $repository,
        BreadCrumbCollection $breadcrumbs,
    ): void {
        $repository_crumb = new BreadCrumb(
            new BreadCrumbLink(
                $repository->getName(),
                $repository->getHtmlPath(),
            ),
        );
        if ($this->getPermissionsManager()->isAdmin($request->getProject(), $request->getCurrentUser())) {
            $settings_link = new BreadCrumbLink(
                dgettext('tuleap-svn', 'Settings'),
                $repository->getSettingUrl(),
            );
            $sub_items     = new BreadCrumbSubItems();
            $sub_items->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection([$settings_link])));

            $repository_crumb->setSubItems($sub_items);
        }
        $breadcrumbs->addFirst(
            $repository_crumb,
        );

        $this->renderInPageWithBodyClass($request, $title, $template, $presenter, $body_class, $breadcrumbs);
    }

    /**
     * @param BaseGlobalAdminPresenter|ExplorerPresenter|AccessControlPresenter|MailNotificationPresenter|HooksConfigurationPresenter|RepositoryDeletePresenter|ImmutableTagPresenter|RepositoryDisplayPresenter $presenter
     */
    private function renderInPageWithBodyClass(
        HTTPRequest $request,
        string $title,
        string $template,
        $presenter,
        string $body_class,
        BreadCrumbCollection $breadcrumbs,
    ): void {
        $this->displaySVNHeader($request, $title, $body_class, $breadcrumbs);
        $this->getRenderer()->renderToPage($template, $presenter);
        $this->displayFooter();
        exit;
    }

    private function getRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(dirname(SVN_BASE_DIR) . '/templates');
    }

    private function displaySVNHeader(HTTPRequest $request, string $title, string $body_class, BreadCrumbCollection $breadcrumbs): void
    {
        $title  = $title . ' - ' . dgettext('tuleap-svn', 'SVN');
        $params = HeaderConfigurationBuilder::get($title)
            ->inProject($this->project, SvnPlugin::SERVICE_SHORTNAME)
            ->withBodyClass([$body_class])
            ->build();

        $repository_list_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext("tuleap-svn", "Repository list"),
                SVN_BASE_URL . "/?group_id=" . $request->getProject()->getId()
            )
        );
        if ($this->getPermissionsManager()->isAdmin($request->getProject(), $request->getCurrentUser())) {
            $admin_link = new BreadCrumbLink(
                _('Administration'),
                GlobalAdministratorsController::getURL($request->getProject()),
            );
            $admin_link->setDataAttribute('test', 'svn-admin-groups');

            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(new SubItemsUnlabelledSection(new BreadCrumbLinkCollection([$admin_link])));

            $repository_list_breadcrumb->setSubItems($sub_items);
        }

        $breadcrumbs->addFirst($repository_list_breadcrumb);
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
