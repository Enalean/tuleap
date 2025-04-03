<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLink;

/**
 * @psalm-immutable
 */
final class ReverseLinkStub implements ReverseLink
{
    private function __construct(private int $id, private string $type)
    {
    }

    public static function withNoType(int $id): self
    {
        return new self($id, \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::NO_TYPE);
    }

    public static function withType(int $id, string $type): self
    {
        return new self($id, $type);
    }

    public function getSourceArtifactId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
