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

namespace Tuleap\OnlyOffice\Save;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Request\NotFoundException;

final class SaveDocumentWithTokenMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SplitTokenIdentifierTranslator $document_download_token_identifier_unserializer,
        private OnlyOfficeSaveDocumentTokenVerifier $document_token_verifier,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $save_token = new ConcealedString($request->getQueryParams()['token'] ?? '');

        if ($save_token->isIdenticalTo(new ConcealedString(''))) {
            return $handler->handle($request);
        }

        try {
            $token = $this->document_download_token_identifier_unserializer->getSplitToken($save_token);
        } catch (SplitTokenException $exception) {
            throw new NotFoundException($exception->getMessage());
        }

        $current_time = new \DateTimeImmutable();
        $token_data   = $this->document_token_verifier->getDocumentSaveTokenData($token, $current_time);

        if ($token_data === null) {
            throw new NotFoundException();
        }

        return $handler->handle(
            $request
                ->withAttribute(SaveDocumentTokenData::class, $token_data)
        );
    }
}
