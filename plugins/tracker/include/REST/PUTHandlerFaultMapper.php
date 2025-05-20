<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Luracast\Restler\RestException;
use Tuleap\NeverThrow\Fault;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\Artifact\ArtifactDoesNotExistFault;
use Tuleap\Tracker\Artifact\Changeset\NoChangeFault;
use Tuleap\Tracker\FormElement\ArtifactLinkFieldDoesNotExistFault;

final class PUTHandlerFaultMapper
{
    /**
     * @throws RestException
     */
    public static function mapToRestException(Fault $fault): void
    {
        if ($fault instanceof NoChangeFault) {
            //Do nothing
            return;
        }
        throw match ($fault::class) {
            ArtifactDoesNotExistFault::class => new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        'You tried to create a link to Artifact #%s, but it could not be found.'
                    ),
                    $fault->artifact_id
                )
            ),
            ArtifactLinkFieldDoesNotExistFault::class => new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        'You tried to create a link to Artifact #%s, but there is no artifact links field in its tracker.'
                    ),
                    $fault->artifact_id
                )
            ),
            default => new RestException(500, (string) $fault)
        };
    }
}
