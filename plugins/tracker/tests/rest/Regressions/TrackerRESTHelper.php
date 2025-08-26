<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Tracker\REST\Regressions;

use Psl\Json;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\REST\RequestWrapper;

final class TrackerRESTHelper
{
    private string $user_name;

    public function __construct(
        private readonly RequestFactoryInterface $request_factory,
        private readonly StreamFactoryInterface $stream_factory,
        private readonly RequestWrapper $rest_request,
        private array $tracker,
        string $default_user_name,
    ) {
        $this->user_name = $default_user_name;
    }

    public function createArtifact(array $values): array
    {
        $post     = Json\encode([
            'tracker' => [
                'id'  => $this->tracker['id'],
                'uri' => 'whatever',
            ],
            'values' => $values,
        ]);
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')
                ->withBody(
                    $this->stream_factory->createStream($post)
                )
        );

        return Json\decode($response->getBody()->getContents());
    }

    /** @return array{field_id: int, value: string} */
    public function getSubmitTextValue(string $field_label, string $field_value): array
    {
        $field_def = $this->getFieldByLabel($field_label);
        return [
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        ];
    }

    /**
     * @return array{field_id: int, bind_value_ids: array{0: int} }
     */
    public function getSubmitListValue(string $field_label, string $field_value_label): array
    {
        $field_def = $this->getFieldByLabel($field_label);
        return [
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => [
                $this->getListValueIdByLabel($field_def, $field_value_label),
            ],
        ];
    }

    private function getListValueIdByLabel(array $field, string $field_value_label): int
    {
        foreach ($field['values'] as $value) {
            if ($value['label'] == $field_value_label) {
                return $value['id'];
            }
        }
        throw new \LogicException("Could not find list value with label '$field_value_label'");
    }

    private function getFieldByLabel(string $field_label): array
    {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['label'] == $field_label) {
                return $field;
            }
        }
        throw new \LogicException("Could not find field with label '$field_label'");
    }

    private function getResponse(RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName(
            $this->user_name,
            $request
        );
    }
}
