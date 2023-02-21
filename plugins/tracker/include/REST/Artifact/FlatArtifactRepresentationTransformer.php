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
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;

/**
 * @psalm-import-type FlatRepresentation from \Tuleap\REST\RESTCollectionTransformer
 */
final class FlatArtifactRepresentationTransformer
{
    public function __construct(
        private readonly RetrieveUsedFields $used_field_retriever,
        private readonly \Codendi_HTMLPurifier $html_purifier,
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
                default:
                    continue 2;
            }
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
}
