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

namespace Tuleap\Tracker\Artifact\ChangesetValue;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use PFUser;
use Psl\Json\Exception\DecodeException;
use Tracker;
use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveAnArtifactLinkField;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkChangesetValueBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValueBuilder;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use function Psl\Json\decode as psl_json_decode;

final readonly class ChangesetValuesContainerBuilder implements BuildChangesetValuesContainer, BuildInitialChangesetValuesContainer
{
    public function __construct(
        private RetrieveAnArtifactLinkField $artifact_link_field_retriever,
        private TreeMapper $mapper,
        private NewArtifactLinkChangesetValueBuilder $link_value_builder,
        private NewArtifactLinkInitialChangesetValueBuilder $initial_link_value_builder,
    ) {
    }

    /**
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    public function buildChangesetValuesContainer(array $fields_data, Tracker $tracker, Artifact $artifact, PFUser $user): ChangesetValuesContainer
    {
        return new ChangesetValuesContainer($fields_data, $this->getFieldDataForArtifactLinkField($tracker, $artifact, $user, $fields_data));
    }

    /**
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    public function buildInitialChangesetValuesContainer(array $fields_data, Tracker $tracker, PFUser $user): InitialChangesetValuesContainer
    {
        return new InitialChangesetValuesContainer($fields_data, $this->getFieldDataForArtifactLinkField($tracker, null, $user, $fields_data));
    }

    /**
     * @psalm-return ($artifact is null ? Option<NewArtifactLinkInitialChangesetValue> : Option<NewArtifactLinkChangesetValue>)
     * @throws Tracker_FormElement_InvalidFieldValueException
     */
    private function getFieldDataForArtifactLinkField(Tracker $tracker, ?Artifact $artifact, PFUser $user, array $fields_data): Option
    {
        $artifact_link_field = $this->artifact_link_field_retriever->getAnArtifactLinkField($user, $tracker);
        if ($artifact_link_field === null) {
            return Option::nothing(NewArtifactLinkChangesetValue::class);
        }
        $field_id = $artifact_link_field->getId();
        if (! isset($fields_data[$field_id])) {
            return Option::nothing(NewArtifactLinkChangesetValue::class);
        }

        if ($artifact_link_field->canEditReverseLinks()) {
            try {
                $payload = $this->mapper->map(ArtifactValuesRepresentation::class, psl_json_decode($fields_data[$field_id]));
            } catch (DecodeException | MappingError) {
                return Option::nothing(NewArtifactLinkChangesetValue::class);
            }
        } else {
            return Option::nothing(NewArtifactLinkChangesetValue::class);
        }

        if ($artifact !== null) {
            return Option::fromValue(
                $this->link_value_builder->buildFromPayload(
                    $artifact,
                    $artifact_link_field,
                    $user,
                    $payload,
                ),
            );
        } else {
            return Option::fromValue(
                $this->initial_link_value_builder->buildFromPayload(
                    $artifact_link_field,
                    $payload,
                ),
            );
        }
    }
}
