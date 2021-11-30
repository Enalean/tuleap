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

use Tuleap\Gitlab\API\ClientWrapper;
use Tuleap\Gitlab\API\Credentials;
use Tuleap\Gitlab\API\GitlabRequestException;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;

class CommentSender
{
    /**
     * @var ClientWrapper
     */
    private $gitlab_api_client;
    /**
     * @var InvalidCredentialsNotifier
     */
    private $notifier;

    public function __construct(ClientWrapper $gitlab_api_client, InvalidCredentialsNotifier $notifier)
    {
        $this->gitlab_api_client = $gitlab_api_client;
        $this->notifier          = $notifier;
    }

    /**
     * @throws \Tuleap\Gitlab\API\GitlabRequestException
     * @throws \Tuleap\Gitlab\API\GitlabResponseAPIException
     */
    public function sendComment(
        GitlabRepositoryIntegration $gitlab_repository_integration,
        Credentials $credentials,
        string $url,
        array $comment_data,
    ): void {
        try {
            $this->gitlab_api_client->postUrl(
                $credentials,
                $url,
                $comment_data
            );
        } catch (GitlabRequestException $e) {
            if ($e->getErrorCode() === 401) {
                $this->notifier->notifyGitAdministratorsThatCredentialsAreInvalid($gitlab_repository_integration, $credentials);
            }
            throw $e;
        }
    }
}
