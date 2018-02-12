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

namespace Tuleap\Git\PerGroup;

use TemplateRendererFactory;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;

class GitPaneSectionCollector
{
    /**
     * @var PermissionPerGroupGitSectionBuilder
     */
    private $git_section_builder;
    /**
     * @var PermissionPerGroupGitRepositoriesSectionBuilder
     */
    private $git_repos_section_builder;

    public function __construct(
        PermissionPerGroupGitSectionBuilder $git_section_builder,
        PermissionPerGroupGitRepositoriesSectionBuilder $git_repos_section_builder
    ) {
        $this->git_section_builder       = $git_section_builder;
        $this->git_repos_section_builder = $git_repos_section_builder;
    }

    public function collectSections(PermissionPerGroupPaneCollector $pane_collector)
    {
        $service_section_presenter     = $this->git_section_builder->buildPresenter($pane_collector);
        $repository_sections_presenter = $this->git_repos_section_builder->buildPresenter($pane_collector);

        $pane_presenter = new GitPanePresenter($service_section_presenter, $repository_sections_presenter);

        $templates_dir = GIT_TEMPLATE_DIR . '/project-admin/';
        $pane          = TemplateRendererFactory::build()
            ->getRenderer($templates_dir)
            ->renderToString('project-admin-permission-per-group', $pane_presenter);

        $pane_collector->addPane($pane);
    }
}
