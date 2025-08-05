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

namespace Tuleap\Artidoc\Adapter\Document;

use Project_NotFoundException;
use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\DecorateArtidocWithContext;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectByIDFactory;

final readonly class ArtidocWithContextDecorator implements DecorateArtidocWithContext
{
    public function __construct(
        private ProjectByIDFactory $project_manager,
        private DocumentServiceFromAllowedProjectRetriever $service_from_allowed_project_retriever,
    ) {
    }

    public function decorate(Artidoc $artidoc): Ok|Err
    {
        try {
            $project = $this->project_manager->getValidProjectById($artidoc->getProjectId());
        } catch (Project_NotFoundException $e) {
            return Result::err(Fault::fromThrowableWithMessage($e, 'Project is not valid'));
        }

        return $this->service_from_allowed_project_retriever
            ->getDocumentServiceFromAllowedProject($project)
            ->map(
                static fn(ServiceDocman $service) => (new ArtidocWithContext($artidoc))
                    ->withContext(ServiceDocman::class, $service)
            );
    }
}
