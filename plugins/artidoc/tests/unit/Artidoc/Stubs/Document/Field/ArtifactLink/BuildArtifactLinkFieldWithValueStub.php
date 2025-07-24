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

namespace Tuleap\Artidoc\Stubs\Document\Field\ArtifactLink;

use Tuleap\Artidoc\Document\Field\ArtifactLink\BuildArtifactLinkFieldWithValue;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\ArtifactLinkFieldWithValue;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;

final class BuildArtifactLinkFieldWithValueStub implements BuildArtifactLinkFieldWithValue
{
    /** @psalm-var callable(ConfiguredField) */
    private $callback;

    private function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public static function withCallback(callable $callback): self
    {
        return new self($callback);
    }

    #[\Override]
    public function buildArtifactLinkFieldWithValue(ConfiguredField $configured_field, ?ArtifactLinkChangesetValue $changeset_value): ArtifactLinkFieldWithValue
    {
        return ($this->callback)($configured_field);
    }
}
