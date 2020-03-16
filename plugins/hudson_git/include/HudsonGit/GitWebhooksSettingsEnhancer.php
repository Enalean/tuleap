<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use GitRepository;
use Tuleap\Git\Webhook\ExternalWebhookPresenter;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;
use Tuleap\HudsonGit\Hook\HookDao;
use Tuleap\HudsonGit\Hook\ModalsPresenter;
use Tuleap\Git\Webhook\SectionOfWebhooksPresenter;
use CSRFSynchronizerToken;
use TemplateRendererFactory;
use Tuleap\HudsonGit\Log\LogFactory;

/**
 * I am responsible of adding the possibility to repo admin to define jenkins hook for a git repository and
 * display relevant information
 */
class GitWebhooksSettingsEnhancer
{

    /**
     * @var LogFactory
     */
    private $log_factory;

    /**
     * @var HookDao
     */
    private $dao;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    /**
     * @var JenkinsServerFactory
     */
    private $jenkins_server_factory;

    public function __construct(
        HookDao $dao,
        LogFactory $log_factory,
        CSRFSynchronizerToken $csrf,
        JenkinsServerFactory $jenkins_server_factory
    ) {
        $this->dao                    = $dao;
        $this->csrf                   = $csrf;
        $this->log_factory            = $log_factory;
        $this->jenkins_server_factory = $jenkins_server_factory;
    }

    public function pimp(array $params): void
    {
        $repository = $params['repository'];
        assert($repository instanceof GitRepository);

        $params['description'] = $GLOBALS['Language']->getText('plugin_hudson_git', 'hooks_desc');

        $project = $repository->getProject();
        $jenkins_servers = $this->jenkins_server_factory->getJenkinsServerOfProject($project);
        $nb_project_jenkins_server = count($jenkins_servers);
        if ($nb_project_jenkins_server > 0) {
            $params['additional_description'] = dngettext(
                'tuleap-hudson_git',
                'A Jenkins server has been defined globally for the project and will be triggered after git pushes.',
                'Some Jenkins servers have been defined globally for the project and will be triggered after git pushes.',
                $nb_project_jenkins_server
            );

            $external_webhook_presenters = [];
            foreach ($jenkins_servers as $jenkins_server) {
                $external_webhook_presenters[] = new ExternalWebhookPresenter(
                    $jenkins_server->getServerURL()
                );
            }
            $params['sections'][] = new SectionOfWebhooksPresenter(
                dgettext("tuleap-git", "Project Jenkins servers"),
                $external_webhook_presenters
            );
        }

        $url = '';
        $dar = $this->dao->searchById($repository->getId());
        $has_already_a_jenkins = count($dar) > 0;
        $params['create_buttons'][] = new GitWebhooksSettingsCreateJenkinsButtonPresenter($has_already_a_jenkins);

        if (count($dar)) {
            $row = $dar->getRow();
            $url = $row['jenkins_server_url'];

            $triggered_jobs = $this->log_factory->getJobByRepository($repository);

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

        $renderer = TemplateRendererFactory::build()->getRenderer(HUDSON_GIT_BASE_DIR . '/templates');
        $params['additional_html_bits'][] = $renderer->renderToString(
            'modals',
            new ModalsPresenter($repository, $url, $this->csrf)
        );
    }
}
