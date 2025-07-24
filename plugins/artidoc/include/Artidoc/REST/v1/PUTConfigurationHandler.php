<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

use PFUser;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_Numeric;
use Tuleap\Artidoc\Document\Field\SuitableFieldRetriever;
use Tuleap\Artidoc\Document\SaveConfiguration;
use Tuleap\Artidoc\Document\Tracker\CheckTrackerIsSuitableForDocument;
use Tuleap\Artidoc\Document\Tracker\TrackerNotFoundFault;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Field\ArtifactSectionField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldDisplayTypeIsUnknownFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldDoesNotBelongToTrackerFault;
use Tuleap\Artidoc\Domain\Document\Section\Field\LinkFieldMustBeDisplayedInBlockFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\Tracker\Tracker;

final readonly class PUTConfigurationHandler
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private SaveConfiguration $save_configuration,
        private RetrieveTracker $retrieve_tracker,
        private CheckTrackerIsSuitableForDocument $suitable_tracker_for_document_checker,
        private SuitableFieldRetriever $retrieve_suitable_field,
    ) {
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function handle(int $id, PUTConfigurationRepresentation $configuration, PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(
                fn(ArtidocWithContext $document_information) => $this->saveConfiguration(
                    $document_information,
                    $configuration,
                    $user
                )
            );
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    private function saveConfiguration(
        ArtidocWithContext $document_information,
        PUTConfigurationRepresentation $configuration,
        PFUser $user,
    ): Ok|Err {
        $tracker = $this->retrieve_tracker->getTrackerById($configuration->selected_tracker_ids[0]);
        if (! $tracker) {
            return Result::err(TrackerNotFoundFault::forDocument($document_information->document));
        }

        return $this->suitable_tracker_for_document_checker->checkTrackerIsSuitableForDocument(
            $tracker,
            $document_information->document,
            $user
        )->andThen(
            fn(Tracker $tracker) => $this->validateAllFields($configuration->fields, $tracker, $user)
                ->map(function (array $fields) use ($document_information, $tracker) {
                    $this->save_configuration->saveConfiguration(
                        $document_information->document->getId(),
                        $tracker->getId(),
                        $fields
                    );
                    return true;
                })
        );
    }

    /**
     * @param ConfiguredFieldRepresentation[] $input_fields
     * @return Ok<list<ArtifactSectionField>>|Err<Fault>
     */
    private function validateAllFields(array $input_fields, Tracker $tracker, PFUser $user): Ok|Err
    {
        /** @var array<int,ArtifactSectionField> $fields */
        $fields = [];
        foreach ($input_fields as $input_field) {
            $result = $this->validateField($input_field, $tracker, $user);
            if (Result::isErr($result)) {
                return $result;
            }
            $output_field                    = $result->value;
            $fields[$output_field->field_id] = $output_field;
        }
        return Result::ok(array_values($fields));
    }

    /**
     * @return Ok<ArtifactSectionField>|Err<Fault>
     */
    private function validateField(ConfiguredFieldRepresentation $input_field, Tracker $tracker, PFUser $user): Ok|Err
    {
        $display_type = DisplayType::tryFrom($input_field->display_type);
        if (! $display_type) {
            return Result::err(
                FieldDisplayTypeIsUnknownFault::build($input_field->field_id, $input_field->display_type)
            );
        }

        return $this->retrieve_suitable_field->retrieveField($input_field->field_id, $user)
            ->andThen(function (StringField|Tracker_FormElement_Field_List|ArtifactLinkField|Tracker_FormElement_Field_Numeric $field) use ($display_type, $tracker) {
                if ($field->getTrackerId() !== $tracker->getId()) {
                    return Result::err(
                        FieldDoesNotBelongToTrackerFault::build($field->getId(), $tracker->getId())
                    );
                }

                if ($field instanceof ArtifactLinkField && $display_type !== DisplayType::BLOCK) {
                    return Result::err(LinkFieldMustBeDisplayedInBlockFault::build());
                }

                return Result::ok(new ArtifactSectionField($field->getId(), $display_type));
            });
    }
}
