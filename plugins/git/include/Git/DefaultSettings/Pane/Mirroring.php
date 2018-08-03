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

namespace Tuleap\Git\DefaultSettings\Pane;

use Git_Mirror_MirrorDataMapper;
use GitPresenters_MirrorPresenter;
use Project;
use TemplateRendererFactory;

class Mirroring extends Pane
{
    const NAME = 'mirroring';
    /**
     * @var Project
     */
    private $project;
    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;

    public function __construct(Git_Mirror_MirrorDataMapper $mirror_data_mapper, Project $project, $is_active)
    {
        parent::__construct(
            ucfirst($GLOBALS['Language']->getText('plugin_git', 'admin_mirroring')),
            "?" . http_build_query(
                [
                    'action'   => 'admin-default-settings',
                    'group_id' => $project->getID(),
                    'pane'     => self::NAME
                ]
            ),
            $is_active,
            false
        );
        $this->project            = $project;
        $this->mirror_data_mapper = $mirror_data_mapper;
    }

    /**
     * @return string
     */
    public function content()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        return $renderer->renderToString(
            'default-mirroring',
            new MirroringPresenter($this->project, $this->getMirrorPresenters())
        );
    }

    private function getMirrorPresenters()
    {
        $mirrors            = $this->mirror_data_mapper->fetchAllForProject($this->project);
        $default_mirror_ids = $this->mirror_data_mapper->getDefaultMirrorIdsForProject($this->project);
        $mirror_presenters  = [];

        foreach ($mirrors as $mirror) {
            $is_used = in_array($mirror->id, $default_mirror_ids);

            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, $is_used);
        }

        return $mirror_presenters;
    }
}
