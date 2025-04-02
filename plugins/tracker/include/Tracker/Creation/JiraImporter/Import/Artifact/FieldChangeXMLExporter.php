<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\ArtifactLinkChange;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeArtifactLinksBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeFileBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\FieldChange\FieldChangeTextBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\ArtifactLinkValue;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\InvalidMappingValueException;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Snapshot\Snapshot;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\ListFieldMapping;

class FieldChangeXMLExporter
{
    public function __construct(
        private LoggerInterface $logger,
        private FieldChangeDateBuilder $field_change_date_builder,
        private FieldChangeStringBuilder $field_change_string_builder,
        private FieldChangeTextBuilder $field_change_text_builder,
        private FieldChangeFloatBuilder $field_change_float_builder,
        private FieldChangeListBuilder $field_change_list_builder,
        private FieldChangeFileBuilder $field_change_file_builder,
        private FieldChangeArtifactLinksBuilder $field_change_artifact_links_builder,
        private GetExistingArtifactLinkTypes $link_type_converter,
    ) {
    }

    public function exportFieldChanges(
        Snapshot $current_snapshot,
        SimpleXMLElement $changeset_node,
    ): void {
        foreach ($current_snapshot->getAllFieldsSnapshot() as $field_snapshot) {
            try {
                $this->exportFieldChange(
                    $field_snapshot->getFieldMapping(),
                    $changeset_node,
                    $field_snapshot->getValue(),
                    $field_snapshot->getRenderedValue()
                );
            } catch (InvalidMappingValueException $exception) {
                $this->logger->warning($field_snapshot->getFieldMapping()->getJiraFieldId() . ' skipped: ' . $exception->getMessage());
            }
        }
    }

    /**
     * @param mixed|null $rendered_value
     * @param mixed $value
     */
    private function exportFieldChange(
        FieldMapping $mapping,
        SimpleXMLElement $changeset_node,
        $value,
        $rendered_value,
    ): void {
        if ($mapping->getType() === Tracker_FormElementFactory::FIELD_STRING_TYPE) {
            $this->field_change_string_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                $value
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_TEXT_TYPE) {
            if ($rendered_value !== null) {
                $this->field_change_text_builder->build(
                    $changeset_node,
                    $mapping->getFieldName(),
                    (string) $rendered_value,
                    Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
                );
            } else {
                $this->field_change_text_builder->build(
                    $changeset_node,
                    $mapping->getFieldName(),
                    is_array($value) ? '' : (string) $value,
                    Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT
                );
            }
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_FLOAT_TYPE) {
            $this->field_change_float_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                (string) $value
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_DATE_TYPE) {
            $this->field_change_date_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                new DateTimeImmutable($value)
            );
        } elseif (
            $mapping->getType() === Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE ||
            $mapping->getType() === Tracker_FormElementFactory::FIELD_RADIO_BUTTON_TYPE
        ) {
            assert(is_array($value));
            if (! $mapping instanceof ListFieldMapping) {
                throw new \RuntimeException('Mapping type ' . $mapping->getType() . ' must be a ' . ListFieldMapping::class);
            }
            assert($mapping->getBindType() !== null);

            if ($mapping->getBindType() === \Tracker_FormElement_Field_List_Bind_Users::TYPE) {
                $value_ids = [
                    $value['id'],
                ];
            } else {
                $mapped_value = $mapping->getValueForId((int) $value['id']);
                if (! $mapped_value) {
                    throw new InvalidMappingValueException($mapping, (string) $value['id']);
                }
                $value_ids = [
                    $mapped_value->getXMLIdValue(),
                ];
            }

            $this->field_change_list_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                $mapping->getBindType(),
                $value_ids,
                []
            );
        } elseif (
            $mapping->getType() === Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE ||
            $mapping->getType() === Tracker_FormElementFactory::FIELD_CHECKBOX_TYPE
        ) {
            assert(is_array($value));
            if (! $mapping instanceof ListFieldMapping) {
                throw new \RuntimeException('Mapping type ' . $mapping->getType() . ' must be a ' . ListFieldMapping::class);
            }
            assert($mapping->getBindType() !== null);

            $value_ids = [];
            if ($mapping->getBindType() === \Tracker_FormElement_Field_List_Bind_Users::TYPE) {
                foreach ($value as $value_from_api) {
                    $value_ids[] = $value_from_api['id'];
                }
            } else {
                foreach ($value as $value_from_api) {
                    $mapped_value = $mapping->getValueForId((int) $value_from_api['id']);
                    if (! $mapped_value) {
                        throw new \RuntimeException('Value ' . $value_from_api['id'] . ' doesnt exist in structure mapping');
                    }
                    $value_ids[] = $mapped_value->getXMLIdValue();
                }
            }

            $this->field_change_list_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                $mapping->getBindType(),
                $value_ids,
                []
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_OPEN_LIST_TYPE) {
            if (! $mapping instanceof ListFieldMapping) {
                throw new \RuntimeException('Mapping type ' . $mapping->getType() . ' must be a ' . ListFieldMapping::class);
            }
            if ($mapping->getBindType() !== \Tracker_FormElement_Field_List_Bind_Static::TYPE) {
                throw new \RuntimeException('Bind type ' . $mapping->getBindType() . ' must only be ' . \Tracker_FormElement_Field_List_Bind_Static::TYPE . ' for OpenList fields.');
            }

            if (is_string($value)) {
                if ($value === '') {
                    $value = [];
                } else {
                    $value = explode(' ', $value);
                }

                if ($value === false) {
                    throw new \RuntimeException('Error while exploding values');
                }
            }

            assert(is_array($value));
            $this->field_change_list_builder->buildAStaticOpenListWithValueLabels(
                $changeset_node,
                $mapping->getFieldName(),
                $value,
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_FILE_TYPE) {
            assert(is_array($value));

            if (count($value) > 0) {
                $this->field_change_file_builder->build(
                    $changeset_node,
                    $mapping->getFieldName(),
                    $value
                );
            }
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_ARTIFACT_LINKS) {
            assert($value instanceof ArtifactLinkValue);

            $field_values = [];
            foreach ($value->issuelinks as $link) {
                if (isset($link['outwardIssue'])) {
                    $artifact_link_type = $this->link_type_converter->getExistingArtifactLinkTypes($link['type']);
                    if (! $artifact_link_type) {
                        $artifact_link_type_shortname = '';
                        $this->logger->warning(sprintf('Issue is linked to issue %s with type %s but this type doesn\'t exist on Tuleap yet. Type skipped.', $link['outwardIssue']['id'], $link['type']['name']));
                    } else {
                        $artifact_link_type_shortname = $artifact_link_type->shortname;
                    }
                    $field_values[] = new ArtifactLinkChange((int) $link['outwardIssue']['id'], $artifact_link_type_shortname);
                }
            }
            foreach ($value->subtasks as $link) {
                $field_values[] = new ArtifactLinkChange((int) $link['id'], \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::TYPE_IS_CHILD);
            }
            $this->field_change_artifact_links_builder->build($changeset_node, $mapping->getFieldName(), $field_values);
        }
    }
}
