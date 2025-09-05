<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Image;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tuleap\PdfTemplate\Image\Identifier\InvalidPdfTemplateImageIdentifierStringException;
use Tuleap\PdfTemplate\Image\Identifier\PdfTemplateImageIdentifierFactory;
use Tuleap\Request\NotFoundException;

final readonly class RetrieveImageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PdfTemplateImageIdentifierFactory $identifier_factory,
        private RetrieveImage $retriever,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = $request->getAttribute('id');
        if (! is_string($id)) {
            throw new NotFoundException();
        }

        try {
            $identifier = $this->identifier_factory->buildFromHexadecimalString($id);
        } catch (InvalidPdfTemplateImageIdentifierStringException) {
            throw new NotFoundException();
        }

        $image = $this->retriever->retrieveImage($identifier);
        if (! $image) {
            throw new NotFoundException();
        }

        $enriched_request = $request->withAttribute(PdfTemplateImage::class, $image);

        return $handler->handle($enriched_request);
    }
}
