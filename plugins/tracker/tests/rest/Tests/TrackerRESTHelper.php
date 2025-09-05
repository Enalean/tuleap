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

namespace Tuleap\Tracker\REST\Tests;

use Psl\Json;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Tuleap\REST\RequestWrapper;

final readonly class TrackerRESTHelper
{
    public function __construct(
        private RequestFactoryInterface $request_factory,
        private StreamFactoryInterface $stream_factory,
        private RequestWrapper $rest_request,
        private array $tracker,
        private string $rest_user_name,
    ) {
    }

    public function createArtifact(array $values): array
    {
        $post     = Json\encode([
            'tracker' => [
                'id'  => $this->getTrackerID(),
            ],
            'values' => $values,
        ]);
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')
                ->withBody($this->stream_factory->createStream($post))
        );

        if ($response->getStatusCode() !== 201) {
            throw new \RuntimeException(sprintf('Could not create artifact in tracker id #%s', $this->getTrackerID()));
        }

        return Json\decode($response->getBody()->getContents());
    }

    /** @return array{field_id: int, value: string} */
    public function getSubmitTextValue(string $field_short_name, string $field_value): array
    {
        $field_def = $this->getFieldByShortName($field_short_name);
        return [
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        ];
    }

    /**
     * @return array{field_id: int, bind_value_ids: array{0: int} }
     */
    public function getSubmitListValue(string $field_short_name, string $field_value_label): array
    {
        $field_def = $this->getFieldByShortName($field_short_name);
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
            if ($value['label'] === $field_value_label) {
                return $value['id'];
            }
        }
        throw new \RuntimeException(sprintf('Could not find list value with label "%s"', $field_value_label));
    }

    /**
     * @return array{field_id: int, type: string, label: string, name: string}
     */
    public function getFieldByShortName(string $field_short_name): array
    {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['name'] === $field_short_name) {
                return $field;
            }
        }
        throw new \RuntimeException(sprintf('Could not find field with short name "%s"', $field_short_name));
    }

    public function getTrackerID(): int
    {
        return $this->tracker['id'];
    }

    private function getResponse(RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName(
            $this->rest_user_name,
            $request
        );
    }
}
