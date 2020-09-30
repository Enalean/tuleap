<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Changeset\Validation;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\ArtifactLinkValidationContext;

/**
 * @psalm-immutable
 */
final class ChangesetWithFieldsValidationContext implements ChangesetValidationContext
{
    /**
     * @var ArtifactLinkValidationContext
     */
    private $artifact_link_context;

    public function __construct(ArtifactLinkValidationContext $artifact_link_context)
    {
        $this->artifact_link_context = $artifact_link_context;
    }

    public function getArtifactLinkContext(): ArtifactLinkValidationContext
    {
        return $this->artifact_link_context;
    }
}
