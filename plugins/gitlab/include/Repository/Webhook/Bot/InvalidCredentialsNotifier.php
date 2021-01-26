<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\Bot;

use MailBuilder;
use Notification;
use Psr\Log\LoggerInterface;
use Tuleap\Git\GitService;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryProjectRetriever;
use Tuleap\Gitlab\Repository\Token\GitlabBotApiTokenDao;
use Tuleap\InstanceBaseURLBuilder;

class InvalidCredentialsNotifier
{
    /**
     * @var MailBuilder
     */
    private $mail_builder;
    /**
     * @var GitlabRepositoryProjectRetriever
     */
    private $repository_project_retriever;
    /**
     * @var InstanceBaseURLBuilder
     */
    private $instance_base_url;
    /**
     * @var GitlabBotApiTokenDao
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        GitlabRepositoryProjectRetriever $repository_project_retriever,
        MailBuilder $mail_builder,
        InstanceBaseURLBuilder $instance_base_url,
        GitlabBotApiTokenDao $dao,
        LoggerInterface $logger
    ) {
        $this->repository_project_retriever = $repository_project_retriever;
        $this->mail_builder                 = $mail_builder;
        $this->instance_base_url            = $instance_base_url;
        $this->dao                          = $dao;
        $this->logger                       = $logger;
    }

    public function notifyGitAdministratorsThatCredentialsAreInvalid(
        GitlabRepository $repository,
        Credentials $credentials
    ): void {
        if ($credentials->getBotApiToken()->isEmailAlreadySendForInvalidToken()) {
            return;
        }

        $at_least_one_email_has_been_sent = false;

        $projects = $this->repository_project_retriever->getProjectsGitlabRepositoryIsIntegratedIn($repository);
        foreach ($projects as $project) {
            $git_service = $project->getService(\GitPlugin::SERVICE_SHORTNAME);
            if (! ($git_service instanceof GitService)) {
                continue;
            }

            $emails = array_filter(
                array_map(
                    function (\PFUser $user): ?string {
                        return $user->isAlive() ? $user->getEmail() : null;
                    },
                    $project->getAdmins()
                )
            );

            $url = $this->instance_base_url->build() . $git_service->getUrl();

            $body = sprintf(
                'It appears that the access token for %s is invalid. Tuleap cannot perform actions on it. Please check configuration on %s',
                $repository->getGitlabRepositoryUrl(),
                $url,
            );

            $notification = new Notification(
                $emails,
                'Invalid GitLab credentials',
                '',
                $body,
                $url,
                'Git',
            );

            $this->mail_builder->buildAndSendEmail($project, $notification, new \MailEnhancer());
            $at_least_one_email_has_been_sent = true;
        }

        if ($at_least_one_email_has_been_sent) {
            $this->logger->info("Notification has been sent to project administrators to warn them that the token appears to be invalid");
            $this->dao->storeTheFactWeAlreadySendEmailForInvalidToken($repository->getId());
        }
    }
}
