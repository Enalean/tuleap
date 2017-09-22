<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Label;

use Tuleap\Label\Exceptions\DuplicatedParameterValueException;
use Tuleap\Label\Exceptions\EmptyParameterException;
use Tuleap\Label\Exceptions\InvalidParameterTypeException;
use Tuleap\Label\Exceptions\MissingMandatoryParameterException;
use Tuleap\REST\JsonDecoder;
use Luracast\Restler\RestException;

class LabeledItemQueryParser
{
    private $json_decoder;

    public function __construct(JsonDecoder $json_decoder)
    {
        $this->json_decoder = $json_decoder;
    }

    /**
     * @param string $query
     * @return int[]
     * @throws DuplicatedParameterValueException
     * @throws EmptyParameterException
     * @throws InvalidParameterTypeException
     * @throws MissingMandatoryParameterException
     */
    public function getLabelIdsFromRoute($query)
    {
        $query = trim($query);
        $json_query = $this->json_decoder->decodeAsAnArray('query', $query);

        if (! isset($json_query['labels_id'])) {
            throw new MissingMandatoryParameterException();
        }

        if (! is_array($json_query['labels_id'])) {
            throw new InvalidParameterTypeException();
        }

        $labels_id = $json_query['labels_id'];

        if (count($labels_id) === 0) {
            throw new EmptyParameterException();
        }

        $only_numeric_label_ids = array_filter($labels_id, 'is_int');
        if ($only_numeric_label_ids !== $labels_id) {
            throw new InvalidParameterTypeException();
        }

        $duplicates = array_diff_key($labels_id, array_unique($labels_id));
        if (count($duplicates) > 0) {
            throw new DuplicatedParameterValueException($duplicates);
        }

        return $labels_id;
    }
}
