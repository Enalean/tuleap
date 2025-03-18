<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Creation\JiraImporter\Import\Artifact;

use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\GetExistingArtifactLinkTypes;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenter;

final readonly class GetExistingArtifactLinkTypesStub implements GetExistingArtifactLinkTypes
{
    private function __construct(private ?TypePresenter $type_presenter)
    {
    }

    public static function build(?TypePresenter $type_presenter): self
    {
        return new self($type_presenter);
    }

    public function getExistingArtifactLinkTypes(array $json_representation): ?TypePresenter
    {
        return $this->type_presenter;
    }
}
