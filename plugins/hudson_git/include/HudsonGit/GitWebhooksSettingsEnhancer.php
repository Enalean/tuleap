<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\HudsonGit;

use Tuleap\HudsonGit\Hook\HookDao;
use Tuleap\HudsonGit\Hook\ModalsPresenter;
use Tuleap\Git\Webhook\SectionOfWebhooksPresenter;
use Tuleap\Git\Webhook\WebhookPresenter;
use Tuleap\HudsonGit\Job\JobManager;
use CSRFSynchronizerToken;
use TemplateRendererFactory;

/**
 * I am responsible of adding the possibility to repo admin to define jenkins hook for a git repository and
 * display relevant information
 */
class GitWebhooksSettingsEnhancer
{

    /**
     * @var JobManager
     */
    private $job_manager;

    /**
     * @var HookDao
     */
    private $dao;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    public function __construct(HookDao $dao, JobManager $job_manager, CSRFSynchronizerToken $csrf)
    {
        $this->dao         = $dao;
        $this->csrf        = $csrf;
        $this->job_manager = $job_manager;
    }

    public function pimp(array $params)
    {
        $repository = $params['repository'];

        $params['description'] = $GLOBALS['Language']->getText('plugin_hudson_git', 'hooks_desc');

        $url = '';
        $dar = $this->dao->searchById($repository->getId());
        $has_already_a_jenkins = count($dar) > 0;
        $params['create_buttons'][] = new GitWebhooksSettingsCreateJenkinsButtonPresenter($has_already_a_jenkins);

        if (count($dar)) {
            $row = $dar->getRow();
            $url = $row['jenkins_server_url'];

            $triggered_jobs = $this->job_manager->getJobByRepository($repository);

            $params['sections'][] = new SectionOfWebhooksPresenter(
                $GLOBALS['Language']->getText('plugin_hudson_git', 'jenkins_hook'),
                array(
                    new JenkinsWebhookPresenter(
                        $repository,
                        $url,
                        $triggered_jobs,
                        $this->csrf
                    )
                )
            );
        }

        $renderer = TemplateRendererFactory::build()->getRenderer(HUDSON_GIT_BASE_DIR.'/templates');
        $params['additional_html_bits'][] = $renderer->renderToString(
            'modals',
            new ModalsPresenter($repository, $url, $this->csrf)
        );
    }
}
