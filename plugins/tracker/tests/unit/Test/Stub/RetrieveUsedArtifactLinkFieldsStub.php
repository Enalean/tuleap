<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveUsedArtifactLinkFields;

final readonly class RetrieveUsedArtifactLinkFieldsStub implements RetrieveUsedArtifactLinkFields
{
    /**
     * @param list<ArtifactLinkField> $fields
     */
    private function __construct(private array $fields)
    {
    }

    /**
     * @return array{0?: ArtifactLinkField}
     */
    public function getUsedArtifactLinkFields(\Tuleap\Tracker\Tracker $tracker): array
    {
        foreach ($this->fields as $field) {
            if ($field->getTrackerId() === $tracker->getId()) {
                return [$field];
            }
        }
        return [];
    }

    public static function withNoField(): self
    {
        return new self([]);
    }

    /**
     * @no-named-arguments
     */
    public static function withFields(ArtifactLinkField $first_field, ArtifactLinkField ...$other_fields): self
    {
        return new self([$first_field, ...$other_fields]);
    }
}
