<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
use Tuleap\Gitlab\Repository\Project\GitlabRepositoryUpdater;
use Tuleap\Gitlab\Repository\Webhook\EventNotAllowedException;
use Tuleap\Gitlab\Repository\Webhook\InvalidValueFormatException;
use Tuleap\Gitlab\Repository\Webhook\MissingEventHeaderException;
use Tuleap\Gitlab\Repository\Webhook\MissingKeyException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotFoundException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotMatchingException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretNotDefinedException;
use Tuleap\Gitlab\Repository\Webhook\WebhookActions;
use Tuleap\Gitlab\Repository\Webhook\WebhookDataExtractor;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Gitlab\Repository\Webhook\EmptyBranchNameException;
use Tuleap\Request\NotFoundException;

class IntegrationWebhookController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    public function __construct(
        private readonly WebhookDataExtractor $webhook_data_extractor,
        private readonly GitlabRepositoryIntegrationFactory $repository_integration_factory,
        private readonly SecretChecker $secret_checker,
        private readonly WebhookActions $webhook_actions,
        private readonly LoggerInterface $logger,
        private readonly ResponseFactoryInterface $response_factory,
        private readonly GitlabRepositoryUpdater $gitlab_repository_updater,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('GitLab webhook received.');
        $current_time   = new DateTimeImmutable();
        $integration_id = (int) $request->getAttribute('integration_id');

        try {
            $gitlab_repository = $this->repository_integration_factory->getIntegrationById($integration_id);
            if ($gitlab_repository === null) {
                throw new NotFoundException(
                    dgettext('tuleap-gitlab', 'The GitLab repository integration cannot be found.')
                );
            }

            $this->secret_checker->checkSecret(
                $gitlab_repository,
                $request
            );

            $webhook_data = $this->webhook_data_extractor->retrieveWebhookData($request);
            $this->webhook_actions->performActions(
                $gitlab_repository,
                $webhook_data,
                $current_time
            );

            $this->gitlab_repository_updater->updateRepositoryDataIfNeeded($gitlab_repository, $webhook_data);

            return $this->response_factory->createResponse(200);
        } catch (
            MissingKeyException |
            EventNotAllowedException |
            SecretHeaderNotFoundException |
            SecretNotDefinedException |
            EmptyBranchNameException |
            SecretHeaderNotMatchingException |
            InvalidValueFormatException |
            MissingEventHeaderException $exception
        ) {
            $this->logger->error($exception->getMessage());
            return $this->response_factory->createResponse(400);
        }
    }
}
