<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile\Board;

/**
 * @psalm-immutable
 */
final class JiraBoardConfiguration
{
    /**
     * @var JiraBoardConfigurationColumn[]
     */
    public $columns;
    /**
     * @var string|null
     */
    public $estimation_field;

    /**
     * @param JiraBoardConfigurationColumn[] $columns
     */
    public function __construct(array $columns, ?string $estimation_field)
    {
        $this->columns          = $columns;
        $this->estimation_field = $estimation_field;
    }

    /**
     * @param JiraBoardConfigurationColumn[] $columns
     */
    public static function buildWithoutEstimationField(array $columns): self
    {
        return new self($columns, null);
    }

    public static function buildFromAPIResponse(array $response): self
    {
        if (
            ! isset($response['columnConfig']) ||
            ! isset($response['columnConfig']['columns']) ||
            ! is_array($response['columnConfig']['columns'])
        ) {
            throw new BoardConfigurationAPIResponseNotWellFormedException();
        }

        $columns = [];
        foreach ($response['columnConfig']['columns'] as $column_response) {
            $columns[] = JiraBoardConfigurationColumn::buildFromAPIResponse($column_response);
        }

        $estimation_field = null;
        if (isset($response['estimation']['type'], $response['estimation']['field']['fieldId']) && $response['estimation']['type'] === 'field') {
            $estimation_field = $response['estimation']['field']['fieldId'];
        }

        return new self($columns, $estimation_field);
    }
}
