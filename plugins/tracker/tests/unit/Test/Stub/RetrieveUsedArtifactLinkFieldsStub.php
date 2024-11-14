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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveUsedArtifactLinkFields;

final class RetrieveUsedArtifactLinkFieldsStub implements RetrieveUsedArtifactLinkFields
{
    /**
     * @param list<\Tracker_FormElement_Field_ArtifactLink> $return_values
     */
    private function __construct(private bool $return_empty, private array $return_values)
    {
    }

    /**
     * @return array{0?: \Tracker_FormElement_Field_ArtifactLink}
     */
    public function getUsedArtifactLinkFields(\Tracker $tracker): array
    {
        if ($this->return_empty) {
            return [];
        }
        if (count($this->return_values) > 0) {
            return [array_shift($this->return_values)];
        }
        throw new \LogicException('No artifact link field configured');
    }

    public static function withNoField(): self
    {
        return new self(true, []);
    }

    public static function withAField(\Tracker_FormElement_Field_ArtifactLink $field): self
    {
        return new self(false, [$field]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveFields(
        \Tracker_FormElement_Field_ArtifactLink $first_field,
        \Tracker_FormElement_Field_ArtifactLink ...$other_fields,
    ): self {
        return new self(false, [$first_field, ...$other_fields]);
    }
}
