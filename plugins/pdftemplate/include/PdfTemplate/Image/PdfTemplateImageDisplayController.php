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

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;

final class PdfTemplateImageDisplayController extends DispatchablePSR15Compatible
{
    public const ROUTE = '/pdftemplate/images';

    public function __construct(
        private readonly BinaryFileResponseBuilder $binary_file_response_builder,
        private readonly PdfTemplateImageStorage $storage,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $image = $request->getAttribute(PdfTemplateImage::class);
        if (! $image instanceof PdfTemplateImage) {
            throw new \LogicException('Image is missing');
        }

        $file_path = $this->storage->getPath($image->identifier);

        return $this->binary_file_response_builder->fromFilePath(
            $request,
            $file_path,
            $image->filename,
            filetype($file_path),
        );
    }
}
