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

namespace Test\Rest\Tracker;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Test\Rest\RequestWrapper;

class Tracker
{
    private RequestFactoryInterface $request_factory;
    private StreamFactoryInterface $stream_factory;
    private $user_name;
    /** @var RequestWrapper */
    private $rest_request;
    private $tracker;

    public function __construct(RequestFactoryInterface $request_factory, StreamFactoryInterface $stream_factory, RequestWrapper $rest_request, array $tracker, $default_user_name)
    {
        $this->request_factory = $request_factory;
        $this->stream_factory  = $stream_factory;
        $this->rest_request    = $rest_request;
        $this->user_name       = $default_user_name;
        $this->tracker         = $tracker;
    }

    public function addCommentToArtifact(array $artifact_reference, $comment)
    {
        return $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                $artifact_reference['uri']
            )->withBody(
                $this->stream_factory->createStream(
                    json_encode(
                        [
                            'values'  => [],
                            'comment' => [
                                'format' => 'text',
                                'body'   => $comment,
                            ],
                        ]
                    )
                )
            )
        );
    }

    public function countArtifacts()
    {
        $request  = $this->request_factory->createRequest('GET', 'trackers/' . $this->tracker['id'] . '/artifacts');
        $response = $this->getResponse($request);
        return (int) $response->getHeaderLine('X-PAGINATION-SIZE');
    }

    public function createArtifact(array $values)
    {
        $post     = json_encode([
            'tracker' => [
                'id'  => $this->tracker['id'],
                'uri' => 'whatever',
            ],
            'values' => $values,
        ]);
        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream($post))
        );

        return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getSubmitTextValue($field_label, $field_value)
    {
        $field_def = $this->getFieldByLabel($field_label);
        return [
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        ];
    }

    public function getSubmitListValue($field_label, $field_value_label)
    {
        $field_def = $this->getFieldByLabel($field_label);
        return [
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => [
                $this->getListValueIdByLabel($field_def, $field_value_label),
            ],
        ];
    }

    public function getSubmitArtifactLinkValue(array $ids)
    {
        return [
            'field_id' => $this->getArtifactLinkFieldId(),
            'links' => array_map(function ($id) {
                return ['id' => $id];
            }, $ids),
        ];
    }

    private function getArtifactLinkFieldId()
    {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['type'] == 'art_link') {
                return $field['field_id'];
            }
        }
        throw new \Exception('No artifact link field for tracker');
    }

    private function getListValueIdByLabel(array $field, $field_value_label)
    {
        foreach ($field['values'] as $value) {
            if ($value['label'] == $field_value_label) {
                return $value['id'];
            }
        }
    }

    private function getFieldByLabel($field_label)
    {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['label'] == $field_label) {
                return $field;
            }
        }
    }

    private function getResponse(RequestInterface $request): ResponseInterface
    {
        return $this->rest_request->getResponseByName(
            $this->user_name,
            $request
        );
    }
}
