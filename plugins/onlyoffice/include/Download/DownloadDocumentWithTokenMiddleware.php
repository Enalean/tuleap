<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Download;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Request\NotFoundException;
use Tuleap\User\ProvideCurrentRequestUser;
use Tuleap\User\RetrieveUserById;

final class DownloadDocumentWithTokenMiddleware implements MiddlewareInterface, ProvideCurrentRequestUser
{
    public function __construct(
        private SplitTokenIdentifierTranslator $document_download_token_identifier_unserializer,
        private OnlyOfficeDownloadDocumentTokenVerifier $document_token_verifier,
        private RetrieveUserById $user_retriever,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $token = $this->document_download_token_identifier_unserializer->getSplitToken(new ConcealedString($request->getQueryParams()['token'] ?? ''));
        } catch (SplitTokenException $exception) {
            throw new NotFoundException($exception->getMessage());
        }

        $current_time = new \DateTimeImmutable();
        $token_data   = $this->document_token_verifier->getDocumentDownloadTokenData($token, $current_time);

        if ($token_data === null) {
            throw new NotFoundException();
        }

        return $handler->handle(
            $request
                ->withAttribute(self::class, $token_data->user_id)
                ->withAttribute('file_id', $token_data->document_id)
        );
    }

    #[\Override]
    public function getCurrentRequestUser(ServerRequestInterface $request): ?\PFUser
    {
        $user_id = $request->getAttribute(self::class);

        if ($user_id === null) {
            return null;
        }

        return $this->user_retriever->getUserById((int) $user_id);
    }
}
