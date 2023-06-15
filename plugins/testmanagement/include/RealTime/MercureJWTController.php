<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\RealTime;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\JWT\generators\MercureJWTGenerator;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\TestManagement\Campaign\ArtifactNotFoundException;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\User\ProvideCurrentUser;

class MercureJWTController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly CampaignRetriever $campaign_retriever,
        private readonly LoggerInterface $logger,
        private readonly ResponseFactoryInterface $response_factory,
        private readonly StreamFactoryInterface $stream_factory,
        private readonly MercureJWTGenerator $mercure_jwt_generator,
        private readonly ProvideCurrentUser $user_provider,
        private readonly ConfigConformanceValidator $conformance_validator,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->user_provider->getCurrentUser();
        if (\ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_TESTMANAGEMENT_KEY) == ! 1) {
            return $this->response_factory->createResponse(404);
        }
        $id = (int) $request->getAttribute("campaign_id");
        try {
            $campaign = $this->campaign_retriever->getById($id);
        } catch (ArtifactNotFoundException $e) {
            $this->logger->info('Artifact error in generating the token in Test Management JWT Request', ['exception' => $e]);
            return $this->response_factory->createResponse(404);
        }
        $artifact = $campaign->getArtifact();
        if (! $this->conformance_validator->isArtifactACampaign($artifact)) {
            $this->logger->info('Not a campaign error in generating the token in Test Managemenent JWT Request');
            return $this->response_factory->createResponse(404);
        }
        if (! $artifact->userCanView($user)) {
            $this->logger->info('User cannot access artifact error in generating the token in Test Management JWT Request');
            return $this->response_factory->createResponse(404);
        }
        $token = $this->mercure_jwt_generator->getTokenWithSubscription('TestManagement', $id, $this->user_provider->getCurrentUser());
        if ($token ===  null) {
            $this->logger->info('Error while generating the token in Test Management JWT Request');
            return $this->response_factory->createResponse(404);
        }
        return $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream(
                $token->getString()
            )
        );
    }
}
