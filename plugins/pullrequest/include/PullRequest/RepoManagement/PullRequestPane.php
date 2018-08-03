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

namespace Tuleap\PullRequest\RepoManagement;

use Codendi_Request;
use GitRepository;
use TemplateRendererFactory;
use Tuleap\Git\GitViews\RepoManagement\Pane\Pane;
use Tuleap\PullRequest\MergeSetting\MergeSettingRetriever;

class PullRequestPane extends Pane
{
    const NAME = 'pullrequest';

    /**
     * @var MergeSettingRetriever
     */
    private $merge_setting_retriever;

    public function __construct(
        GitRepository $repository,
        Codendi_Request $request,
        MergeSettingRetriever $merge_setting_retriever
    ) {
        parent::__construct($repository, $request);
        $this->merge_setting_retriever = $merge_setting_retriever;
    }

    /**
     * @return string eg: 'perms'
     */
    public function getIdentifier()
    {
        return self::NAME;
    }

    /**
     * @return string eg: 'Accesss Control'
     */
    public function getTitle()
    {
        return dgettext('tuleap-pullrequest', 'Pull requests');
    }

    /**
     * @return string eg: '<form>...</form>'
     */
    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(PULLREQUEST_BASE_DIR . "/templates");

        $merge_setting = $this->merge_setting_retriever->getMergeSettingForRepository($this->repository);

        return $renderer->renderToString(
            'repository-settings',
            new PullRequestPanePresenter($this->repository, $merge_setting)
        );
    }
}
