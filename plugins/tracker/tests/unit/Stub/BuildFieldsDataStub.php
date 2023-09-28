<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfForwardLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\CollectionOfReverseLinks;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValuesContainer;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\BuildFieldsData;

/**
 * @psalm-immutable
 */
final class BuildFieldsDataStub implements BuildFieldsData
{
    private function __construct(
        private array $fields_data,
        private NewArtifactLinkInitialChangesetValue $artifact_link_value,
    ) {
    }

    public static function buildWithDefaultsForInitialChangeset(): self
    {
        $fields_data         = [1 => "A field"];
        $new_links           = new CollectionOfForwardLinks([
            ForwardLinkStub::withType(41, 'custom_type'),
            ForwardLinkStub::withNoType(91),
        ]);
        $reverse_links       = new CollectionOfReverseLinks([
            ReverseLinkStub::withType(56, 'custom_type'),
        ]);
        $artifact_link_value = NewArtifactLinkInitialChangesetValue::fromParts(
            122,
            $new_links,
            null,
            $reverse_links
        );

        return new self($fields_data, $artifact_link_value);
    }

    public function getFieldsDataOnCreate(array $values, \Tracker $tracker): InitialChangesetValuesContainer
    {
        return new InitialChangesetValuesContainer($this->fields_data, $this->artifact_link_value);
    }

    public function getFieldsDataOnUpdate(
        array $values,
        Artifact $artifact,
        \PFUser $submitter,
    ): ChangesetValuesContainer {
        return new ChangesetValuesContainer([], Option::nothing(NewArtifactLinkChangesetValue::class));
    }
}
