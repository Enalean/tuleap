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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Stub;

use Tracker;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveAnArtifactLinkField;

final class RetrieveAnArtifactLinkFieldStub implements RetrieveAnArtifactLinkField
{
    private function __construct(private readonly ?Tracker_FormElement_Field_ArtifactLink $field)
    {
    }

    public static function withAnArtifactLinkField(Tracker_FormElement_Field_ArtifactLink $field): self
    {
        return new self($field);
    }

    public static function withoutAnArtifactLinkField(): self
    {
        return new self(null);
    }

    public function getAnArtifactLinkField(\PFUser $user, Tracker $tracker): ?Tracker_FormElement_Field_ArtifactLink
    {
        return $this->field;
    }
}
