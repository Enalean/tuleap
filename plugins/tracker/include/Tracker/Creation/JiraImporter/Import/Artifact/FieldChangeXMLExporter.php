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
use SimpleXMLElement;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Values\StatusValuesTransformer;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeListBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;

class FieldChangeXMLExporter
{
    /**
     * @var FieldChangeDateBuilder
     */
    private $field_change_date_builder;

    /**
     * @var FieldChangeStringBuilder
     */
    private $field_change_string_builder;

    /**
     * @var FieldChangeTextBuilder
     */
    private $field_change_text_builder;

    /**
     * @var FieldChangeFloatBuilder
     */
    private $field_change_float_builder;

    /**
     * @var FieldChangeListBuilder
     */
    private $field_change_list_builder;

    /**
     * @var StatusValuesTransformer
     */
    private $status_values_transformer;

    public function __construct(
        FieldChangeDateBuilder $field_change_date_builder,
        FieldChangeStringBuilder $field_change_string_builder,
        FieldChangeTextBuilder $field_change_text_builder,
        FieldChangeFloatBuilder $field_change_float_builder,
        FieldChangeListBuilder $field_change_list_builder,
        StatusValuesTransformer $status_values_transformer
    ) {
        $this->field_change_date_builder   = $field_change_date_builder;
        $this->field_change_string_builder = $field_change_string_builder;
        $this->field_change_text_builder   = $field_change_text_builder;
        $this->field_change_float_builder  = $field_change_float_builder;
        $this->field_change_list_builder   = $field_change_list_builder;
        $this->status_values_transformer   = $status_values_transformer;
    }

    /**
     * @param mixed|null $rendered_value
     * @param mixed $value
     */
    public function exportFieldChange(
        FieldMapping $mapping,
        SimpleXMLElement $changeset_node,
        SimpleXMLElement $node_submitted_on,
        $value,
        $rendered_value
    ): void {
        if ($mapping->getType() === Tracker_FormElementFactory::FIELD_STRING_TYPE) {
            $this->field_change_string_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                $value
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_TEXT_TYPE) {
            $this->field_change_text_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                (string) $rendered_value,
                Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT
            );
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
            $value_ids = [
                $value['id']
            ];

            if ($mapping->getFieldName() === AlwaysThereFieldsExporter::JIRA_STATUS_NAME) {
                $value_ids = [
                    $this->status_values_transformer->transformJiraStatusValue((int) $value['id'])
                ];
            }

            $this->field_change_list_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                $value_ids
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_MULTI_SELECT_BOX_TYPE) {
            assert(is_array($value));
            $value_ids = [];
            foreach ($value as $value_from_api) {
                $value_ids[] = $value_from_api['id'];
            }

            $this->field_change_list_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                Tracker_FormElement_Field_List_Bind_Static::TYPE,
                $value_ids
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE) {
            $node_submitted_on[0] = $value;
        }
    }
}
