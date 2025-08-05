<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Upload\Section\File\ArtidocUploadPathAllocator;
use Tuleap\Artidoc\Upload\Section\File\SearchUpload;
use Tuleap\Artidoc\Upload\Section\File\UploadFileInformation;
use Tuleap\Artidoc\Upload\Section\File\UploadedFileWithArtidoc;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;
use Tuleap\Tus\Identifier\FileIdentifier;
use Tuleap\Tus\Identifier\FileIdentifierFactory;
use Tuleap\Tus\Identifier\InvalidFileIdentifierStringException;

final class ArtidocAttachmentController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    public const ROUTE_PREFIX = '/artidoc/attachments';

    public function __construct(
        private readonly RetrieveArtidocWithContext $retrieve_artidoc,
        private readonly FileIdentifierFactory $identifier_factory,
        private readonly SearchUpload $search,
        private readonly BinaryFileResponseBuilder $response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        ServiceInstrumentation::increment('artidoc');

        try {
            $id = $this->identifier_factory->buildFromHexadecimalString($request->getAttribute('id'));
        } catch (InvalidFileIdentifierStringException) {
            throw new NotFoundException();
        }

        return $this->getUploadedFileWithArtidoc($id)
            ->match(
                function (UploadedFileWithArtidoc $upload) use ($request) {
                    $path_allocator = ArtidocUploadPathAllocator::fromArtidoc($upload->artidoc);

                    return $this->response_builder->fromFilePath(
                        $request,
                        $path_allocator->getPathForItemBeingUploaded($upload->file),
                        $upload->file->getName(),
                    );
                },
                static fn () => throw new NotFoundException(),
            );
    }

    /**
     * @return Ok<UploadedFileWithArtidoc>|Err<Fault>
     */
    private function getUploadedFileWithArtidoc(FileIdentifier $id): Ok|Err
    {
        return $this->search->searchUpload($id)
            ->andThen(fn (UploadFileInformation $file) =>
                $this->retrieve_artidoc->retrieveArtidocUserCanRead($file->artidoc_id)->map(
                    static fn (ArtidocWithContext $artidoc) => new UploadedFileWithArtidoc($file, $artidoc->document)
                ));
    }
}
