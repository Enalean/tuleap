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

namespace Tuleap\PullRequest\DefaultSettings;

use Project;
use TemplateRendererFactory;
use Tuleap\Git\DefaultSettings\Pane\Pane;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;

final class PullRequestPane extends Pane
{
    const NAME = 'pullrequest';

    /**
     * @var MergeSettingRetriever
     */
    private $merge_setting_retriever;
    /**
     * @var Project
     */
    private $project;

    public function __construct(
        MergeSettingRetriever $merge_setting_retriever,
        Project $project,
        $is_active
    ) {
        parent::__construct(
            dgettext('tuleap-pullrequest', 'Pull requests'),
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
        $this->merge_setting_retriever = $merge_setting_retriever;
        $this->project                 = $project;
    }

    /**
     * @return string
     */
    public function content()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(PULLREQUEST_BASE_DIR . "/templates");

        $merge_setting = $this->merge_setting_retriever->getMergeSettingForProject($this->project);

        return $renderer->renderToString(
            'default-settings',
            new PullRequestPanePresenter($this->project, $merge_setting)
        );
    }
}
