<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
use Tracker_FormElementFactory;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeDateBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeFloatBuilder;
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

    public function __construct(
        FieldChangeDateBuilder $field_change_date_builder,
        FieldChangeStringBuilder $field_change_string_builder,
        FieldChangeTextBuilder $field_change_text_builder,
        FieldChangeFloatBuilder $field_change_float_builder
    ) {
        $this->field_change_date_builder   = $field_change_date_builder;
        $this->field_change_string_builder = $field_change_string_builder;
        $this->field_change_text_builder   = $field_change_text_builder;
        $this->field_change_float_builder  = $field_change_float_builder;
    }

    /**
     * @param mixed|null $rendered_value
     */
    public function exportFieldChange(
        FieldMapping $mapping,
        SimpleXMLElement $changeset_node,
        SimpleXMLElement $node_submitted_on,
        string $value,
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
                $value
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_DATE_TYPE) {
            $this->field_change_date_builder->build(
                $changeset_node,
                $mapping->getFieldName(),
                new DateTimeImmutable($value)
            );
        } elseif ($mapping->getType() === Tracker_FormElementFactory::FIELD_LAST_UPDATE_DATE_TYPE) {
            $node_submitted_on[0] = $value;
        }
    }
}
