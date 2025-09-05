<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Docman\Metadata\Owner;

use JsonException;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Project;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Document\Tree\IExtractProjectFromVariables;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Project\CheckProjectAccess;
use Tuleap\Project\ProjectAccessSuspendedException;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\User\ProvideCurrentUser;

final class OwnerRequestHandler extends DispatchablePSR15Compatible implements DispatchableWithProject, DispatchableWithRequestNoAuthz
{
    public function __construct(
        private RetrieveAllOwner $owner_retriever,
        private CheckProjectAccess $project_access_checker,
        private ProvideCurrentUser $user_manager,
        private IExtractProjectFromVariables $project_extractor,
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        private JSONResponseBuilder $json_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    /**
     * @throws JsonException
     * @throws NotFoundException
     */
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user     = $this->user_manager->getCurrentUser();
        $request_variable = $request->getAttributes();
        $project          = $this->getProject($request_variable);
        try {
            $this->project_access_checker->checkUserCanAccessProject($current_user, $project);

            $request_params = $request->getQueryParams();
            if (! isset($request_params['name']) || $request_params['name'] === '') {
                return $this->response_factory->createResponse(400)->withHeader('Content-Type', 'text/plain')->withBody(
                    $this->stream_factory->createStream("Bad request: The query parameter 'name' is missing or empty")
                );
            }
            $name_to_search = $request_params['name'];

            $project_document_owners = $this->owner_retriever->retrieveProjectDocumentOwnersForAutocomplete($project, $name_to_search);

            return $this->json_response_builder->fromData(['results' => $project_document_owners]);
        } catch (
            \Project_AccessDeletedException
            | \Project_AccessPrivateException
            | \Project_AccessProjectNotFoundException
            | \Project_AccessRestrictedException
            | ProjectAccessSuspendedException $e
        ) {
            return $this->response_factory->createResponse(403)->withHeader('Content-Type', 'text/plain')->withBody(
                $this->stream_factory->createStream('Forbidden: Your not allowed to access this resource')
            );
        }
    }

    /**
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): Project
    {
        return $this->project_extractor->getProject($variables);
    }
}
