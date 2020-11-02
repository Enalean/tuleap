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

namespace Tuleap\Gitlab\Repository;

use DateTimeImmutable;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\EventNotAllowedException;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;
use Tuleap\Gitlab\Repository\Webhook\RepositoryNotFoundException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotFoundException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotMatchingException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretNotDefinedException;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataExtractor;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class GitlabRepositoryWebhookController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var WebhookDataExtractor
     */
    private $webhook_data_extractor;

    /**
     * @var GitlabRepositoryDao
     */
    private $gitlab_repository_dao;

    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        WebhookDataExtractor $webhook_data_extractor,
        GitlabRepositoryDao $gitlab_repository_dao,
        ResponseFactoryInterface $response_factory,
        LoggerInterface $logger,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);

        $this->webhook_data_extractor = $webhook_data_extractor;
        $this->gitlab_repository_dao  = $gitlab_repository_dao;
        $this->response_factory       = $response_factory;
        $this->logger                 = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info("GitLab webhook received.");
        $current_time = new DateTimeImmutable();

        try {
            $gitlab_repository = $this->webhook_data_extractor->retrieveRepositoryFromWebhookContent(
                $request
            );

            $this->gitlab_repository_dao->updateLastPushDateForRepository(
                $gitlab_repository->getId(),
                $current_time->getTimestamp()
            );

            $this->logger->info("Last update date successfully updated for GitLab repository #" . $gitlab_repository->getId());
            return $this->response_factory->createResponse(200);
        } catch (RepositoryNotFoundException $exception) {
            $this->logger->error($exception->getMessage());
            return $this->response_factory->createResponse(404);
        } catch (
            MissingKeyException |
            EventNotAllowedException |
            SecretHeaderNotFoundException |
            SecretNotDefinedException |
            SecretHeaderNotMatchingException $exception
        ) {
            $this->logger->error($exception->getMessage());
            return $this->response_factory->createResponse(400);
        }
    }
}
