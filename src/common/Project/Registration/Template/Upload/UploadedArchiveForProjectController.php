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

namespace Tuleap\Project\Registration\Template\Upload;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\NotFoundException;

final class UploadedArchiveForProjectController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly BinaryFileResponseBuilder $response_builder,
        private readonly RetrieveUploadedArchiveForProject $uploaded_archive_for_project_retriever,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        $path = $this->uploaded_archive_for_project_retriever->searchByProjectId((int) $project->getID());
        if (! $path || ! \Psl\Filesystem\is_file($path)) {
            throw new NotFoundException();
        }

        return $this->response_builder->fromFilePath($request, $path, 'uploaded-archive-for-' . $project->getUnixNameMixedCase() . '.zip');
    }

    public static function getUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/uploaded-archive';
    }
}
