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
use Tuleap\Notification\Notification;
use Psr\Log\LoggerInterface;
use Tuleap\Git\GitService;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Token\IntegrationApiToken;
use Tuleap\Gitlab\Repository\Token\IntegrationApiTokenDao;
use Tuleap\ServerHostname;

class InvalidCredentialsNotifier
{
    /**
     * @var MailBuilder
     */
    private $mail_builder;
    /**
     * @var IntegrationApiTokenDao
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        MailBuilder $mail_builder,
        IntegrationApiTokenDao $dao,
        LoggerInterface $logger,
    ) {
        $this->mail_builder = $mail_builder;
        $this->dao          = $dao;
        $this->logger       = $logger;
    }

    public function notifyGitAdministratorsThatCredentialsAreInvalid(
        GitlabRepositoryIntegration $repository_integration,
        Credentials $credentials,
    ): void {
        $bot_api_token = $credentials->getApiToken();
        assert($bot_api_token instanceof IntegrationApiToken);
        if ($bot_api_token->isEmailAlreadySendForInvalidToken()) {
            return;
        }

        $project     = $repository_integration->getProject();
        $git_service = $project->getService(\GitPlugin::SERVICE_SHORTNAME);
        if (! ($git_service instanceof GitService)) {
            return;
        }

        $emails = array_filter(
            array_map(
                function (\PFUser $user): ?string {
                    return $user->isAlive() ? $user->getEmail() : null;
                },
                $project->getAdmins()
            )
        );

        $url = ServerHostname::HTTPSUrl() . GitService::getServiceUrlForProject($project);

        $body = sprintf(
            'It appears that the access token for %s is invalid. Tuleap cannot perform actions on it. Please check configuration on %s',
            $repository_integration->getGitlabRepositoryUrl(),
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

        $this->logger->info("Notification has been sent to project administrators to warn them that the token appears to be invalid");
        $this->dao->storeTheFactWeAlreadySendEmailForInvalidToken($repository_integration->getId());
    }
}
