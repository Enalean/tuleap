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

namespace Tuleap\Tracker\Artifact\Creation;

use Exception;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistException;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistException;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistFault;
use Tuleap\Tracker\Semantic\SemanticNotSupportedException;
use Tuleap\Tracker\Semantic\SemanticNotSupportedFault;

final class FaultMapper
{
    /**
     * @throws ArtifactDoesNotExistException
     * @throws ArtifactLinkFieldDoesNotExistException
     * @throws SemanticNotSupportedException
     * @throws Exception
     */
    public static function mapToException(Fault $fault): never
    {
        throw match ($fault::class) {
            ArtifactDoesNotExistFault::class => new ArtifactDoesNotExistException((string) $fault),
            ArtifactLinkFieldDoesNotExistFault::class => new ArtifactLinkFieldDoesNotExistException((string) $fault),
            SemanticNotSupportedFault::class => new SemanticNotSupportedException((string) $fault),
            default => new Exception((string) $fault),
        };
    }
}
