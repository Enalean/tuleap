<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\Option\Options;
use Tuleap\Project\REST\MinimalUserGroupRepresentation;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\User\REST\UserRepresentation;

/**
 * @psalm-import-type FlatRepresentation from \Tuleap\REST\RESTCollectionTransformer
 */
final readonly class FlatArtifactRepresentationTransformer
{
    public function __construct(
        private RetrieveUsedFields $used_field_retriever,
        private \Codendi_HTMLPurifier $html_purifier,
        private FlatArtifactListValueLabelTransformer $value_labels_transformer,
    ) {
    }

    /**
     * @psalm-return Ok<FlatRepresentation>|Err<Fault>
     */
    public function __invoke(ArtifactRepresentation $artifact_representation): Ok|Err
    {
        if ($artifact_representation->values === null) {
            return Result::err(Fault::fromMessage('No values in the artifact representation, check the query parameters'));
        }

        $flat_representation_faults = [];

        $flat_representation = [];
        foreach ($artifact_representation->values as $field) {
            switch ($field->type) {
                case 'string':
                case 'int':
                case 'float':
                case 'aid':
                case 'atid':
                case 'priority':
                case 'date':
                case 'lud':
                case 'subon':
                    $flat_representation[$this->getFieldName($field->field_id)] = $field->value;
                    break;
                case 'text':
                    $flat_representation[$this->getFieldName($field->field_id)] = $field->format === 'text' ?
                        $field->value :
                        $this->html_purifier->purify($field->value, \Codendi_HTMLPurifier::CONFIG_STRIP_HTML);
                    break;
                case 'computed':
                    $flat_representation[$this->getFieldName($field->field_id)] = $field->is_autocomputed ? $field->value : $field->manual_value;
                    break;
                case 'sb':
                case 'msb':
                case 'rb':
                case 'cb':
                case 'tbl':
                    $field_id   = $field->field_id;
                    $field_name = $this->getFieldName($field_id);
                    $this->transformListValues($artifact_representation->id, $field_id, $field_name, $field)
                        ->match(
                            /**
                             * @psalm-param string|list<string> $value
                             */
                            function (mixed $value) use (&$flat_representation, $field_name): void {
                                $flat_representation[$field_name] = $value;
                            },
                            function (Fault $fault) use (&$flat_representation_faults): void {
                                $flat_representation_faults[] = $fault;
                            },
                        );
                    break;
                case 'subby':
                case 'luby':
                    $flat_representation[$this->getFieldName($field->field_id)] = $this->getListValueLabel($field->value)->unwrapOr(null);
                    break;
                default:
                    continue 2;
            }
        }

        if (count($flat_representation_faults) > 0) {
            $all_fault_messages = '';
            foreach ($flat_representation_faults as $flat_representation_fault) {
                $all_fault_messages .= "$flat_representation_fault. ";
            }
            return Result::err(Fault::fromMessage(trim($all_fault_messages)));
        }

        return Result::ok($flat_representation);
    }

    private function getFieldName(int $field_id): string
    {
        $field = $this->used_field_retriever->getUsedFormElementFieldById($field_id);
        if ($field === null) {
            throw new \LogicException(
                sprintf('Field #%d not found in the used form element but used in artifact representation', $field_id)
            );
        }

        return $field->getName();
    }

    /**
     * @psalm-return Ok<string>|Ok<list<string>>|Err<Fault>
     */
    private function transformListValues(int $artifact_id, int $field_id, string $field_name, object $field): Ok|Err
    {
        if ($field->type === 'tbl') {
            $list_values = $field->bind_value_objects;
        } else {
            $list_values = $field->values;
        }
        $value_labels = [];
        foreach ($list_values as $list_value) {
            $value_labels[] = $this->getListValueLabel($list_value);
        }
        return $this->value_labels_transformer->transformListValueLabels(
            $artifact_id,
            $field_id,
            $field_name,
            Options::collect($value_labels)
        );
    }

    /**
     * @psalm-return Option<string>
     */
    private function getListValueLabel(array|UserRepresentation|MinimalUserGroupRepresentation $list_value): Option
    {
        if (is_array($list_value)) {
            return isset($list_value['label']) ? Option::fromValue($list_value['label']) : Option::nothing(\Psl\Type\string());
        }

        if ($list_value instanceof MinimalUserGroupRepresentation) {
            return Option::fromValue($list_value->label);
        }

        if ($list_value->id !== null) {
            return Option::fromValue($list_value->display_name);
        }

        return Option::nothing(\Psl\Type\string());
    }
}
