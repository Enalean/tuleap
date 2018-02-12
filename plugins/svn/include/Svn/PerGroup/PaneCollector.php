<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Svn\PerGroup;

use ForgeConfig;
use TemplateRendererFactory;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use UGroupManager;

class PaneCollector
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerGroupSVNServicePaneBuilder
     */
    private $group_pane_builder;
    /**
     * @var PermissionPerGroupRepositoryPaneBuilder
     */
    private $repository_pane_builder;

    public function __construct(
        UGroupManager $ugroup_manager,
        PermissionPerGroupSVNServicePaneBuilder $group_pane_builder,
        PermissionPerGroupRepositoryPaneBuilder $repository_pane_builder
    ) {
        $this->ugroup_manager          = $ugroup_manager;
        $this->group_pane_builder      = $group_pane_builder;
        $this->repository_pane_builder = $repository_pane_builder;
    }

    public function collectPane(PermissionPerGroupPaneCollector $event)
    {
        $service_presenter    = $this->getServicePresenter($event);
        $repository_presenter = $this->getRepositoryPresenter($event);

        $global_presenter = new GlobalPresenter($service_presenter, $repository_presenter);

        $templates_dir = ForgeConfig::get('tuleap_dir') . '/plugins/svn/templates/';
        $content       = TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $global_presenter);

        $event->addPane($content);
    }

    /**
     * @param PermissionPerGroupPaneCollector $event
     *
     * @return PermissionPerGroupPanePresenter
     */
    private function getServicePresenter(PermissionPerGroupPaneCollector $event)
    {
        return $this->group_pane_builder->buildPresenter($event);
    }

    /**
     * @param PermissionPerGroupPaneCollector $event
     *
     * @return PermissionPerGroupPanePresenter
     */
    private function getRepositoryPresenter(PermissionPerGroupPaneCollector $event)
    {
        return $this->repository_pane_builder->buildPresenter($event);
    }
}
