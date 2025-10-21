<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Reference;

use JsonException;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Override;
use Project_AccessException;
use Project_NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;

final class CrossReferencesRetrieverController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly CrossReferenceByDirectionPresenterBuilder $builder,
        private readonly ProvideCurrentUser $current_user_provider,
        private readonly JSONResponseBuilder $response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $item_id      = $request->getAttribute('item_id');
        $project_id   = (int) $request->getAttribute('project_id');
        $current_user = $this->current_user_provider->getCurrentUser();

        $query = $request->getQueryParams();
        if (! isset($query['type'])) {
            return $this->buildErrorResponse(400, 'GET parameter "type" is required');
        }
        $type = $query['type'];

        try {
            return $this->response_builder->fromData(
                $this->builder->build($item_id, $type, $project_id, $current_user)->toArray()
            )->withStatus(200);
        } catch (JsonException) {
            return $this->buildErrorResponse(500, 'Failed to parse response');
        } catch (Project_NotFoundException) {
            return $this->buildErrorResponse(404, 'Not Found');
        } catch (Project_AccessException) {
            return $this->buildErrorResponse(403, 'Forbidden');
        }
    }

    private function buildErrorResponse(int $code, string $message): ResponseInterface
    {
        return $this->response_builder->fromData(['error' => ['message' => $message]])->withStatus($code);
    }
}
