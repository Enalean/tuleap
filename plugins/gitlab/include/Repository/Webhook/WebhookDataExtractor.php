<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotFoundException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotMatchingException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretNotDefinedException;

class WebhookDataExtractor
{
    private const EVENT_NAME_KEY = 'event_name';
    private const PROJECT_KEY = 'project';
    private const PROJECT_ID_KEY = 'id';
    private const PROJECT_URL_KEY = 'web_url';
    private const PUSH_EVENT = 'push';

    /**
     * @var GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    /**
     * @var SecretChecker
     */
    private $secret_checker;

    public function __construct(
        GitlabRepositoryFactory $gitlab_repository_factory,
        SecretChecker $secret_checker
    ) {
        $this->gitlab_repository_factory = $gitlab_repository_factory;
        $this->secret_checker            = $secret_checker;
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     * @throws RepositoryNotFoundException
     * @throws SecretHeaderNotFoundException
     * @throws SecretNotDefinedException
     * @throws SecretHeaderNotMatchingException
     */
    public function retrieveRepositoryFromWebhookContent(ServerRequestInterface $request): GitlabRepository
    {
        $webhook_content = json_decode($request->getBody()->getContents(), true);
        $this->checkExpectedJsonKeysAreSet($webhook_content);

        $gitlab_repository = $this->getRepositoryObject($webhook_content);
        if ($gitlab_repository === null) {
            throw new RepositoryNotFoundException();
        }

        $this->secret_checker->checkSecret(
            $gitlab_repository,
            $request
        );

        return $gitlab_repository;
    }

    /**
     * @throws MissingKeyException
     * @throws EventNotAllowedException
     */
    private function checkExpectedJsonKeysAreSet(array $webhook_content): void
    {
        if (! isset($webhook_content[self::EVENT_NAME_KEY])) {
            throw new MissingKeyException(self::EVENT_NAME_KEY);
        }

        if ($webhook_content[self::EVENT_NAME_KEY] !== self::PUSH_EVENT) {
            throw new EventNotAllowedException($webhook_content[self::EVENT_NAME_KEY]);
        }

        if (! isset($webhook_content[self::PROJECT_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . " > " . self::PROJECT_ID_KEY);
        }

        if (! isset($webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY])) {
            throw new MissingKeyException(self::PROJECT_KEY . " > " . self::PROJECT_URL_KEY);
        }
    }

    private function getRepositoryObject(array $webhook_content): ?GitlabRepository
    {
        return $this->gitlab_repository_factory->getGitlabRepositoryByInternalIdAndPath(
            (int) $webhook_content[self::PROJECT_KEY][self::PROJECT_ID_KEY],
            (string) $webhook_content[self::PROJECT_KEY][self::PROJECT_URL_KEY]
        );
    }
}
