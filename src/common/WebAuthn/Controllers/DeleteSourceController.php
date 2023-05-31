<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn\Controllers;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;
use Webauthn\PublicKeyCredentialSourceRepository;
use function Psl\Encoding\Base64\decode;

final class DeleteSourceController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly ProvideCurrentUser $user_manager,
        private readonly PublicKeyCredentialSourceRepository $source_dao,
        private readonly RestlerErrorResponseBuilder $error_response_builder,
        private readonly ResponseFactoryInterface $response_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return $this->error_response_builder->build(401);
        }

        try {
            $key_id = decode($request->getAttribute('key_id'));
        } catch (\Throwable) {
            return $this->error_response_builder->build(400, _('Credential id is not well formed'));
        }

        $source = $this->source_dao->findOneByCredentialId($key_id);
        if ($source === null) {
            return $this->response_factory->createResponse(200);
        }
        if ($source->getUserHandle() !== (string) $current_user->getId()) {
            return $this->response_factory->createResponse(200);
        }

        // Delete source

        return $this->error_response_builder->build(501);
    }
}
