<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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

namespace TuleapClient;

class Tracker {
    /** @var Request */
    private $request;
    private $tracker;

    public function __construct(Request $request, array $tracker) {
        $this->request = $request;
        $this->tracker = $tracker;
    }

    public function addCommentToArtifact(array $artifact_reference, $comment) {
        return $this->request->send(
            $this->request->getClient()->put(
                $artifact_reference['uri'],
                null,
                json_encode(
                    array(
                        'values'  => array(),
                        'comment' => array(
                            'format' => 'text',
                            'body'   => $comment,
                        ),
                    )
                )
            )
        );
    }

    public function createArtifact(array $values) {
        $post = json_encode(array(
            'tracker' => array(
                'id'  => $this->tracker['id'],
                'uri' => 'whatever'
            ),
            'values' => $values,
        ));
        return $this->request->getJson($this->request->getClient()->post('artifacts', null, $post));
    }

    public function getSubmitTextValue($field_label, $field_value) {
        $field_def = $this->getFieldByLabel($field_label);
        return array(
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        );
    }

    public function getSubmitListValue($field_label, $field_value_label) {
        $field_def = $this->getFieldByLabel($field_label);
        return array(
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => array(
                $this->getListValueIdByLabel($field_def, $field_value_label)
            ),
        );
    }

    public function getSubmitArtifactLinkValue(array $ids) {
        return array(
            'field_id' => $this->getArtifactLinkFieldId(),
            'links' => array_map(function ($id) { return array('id' => $id); }, $ids)
        );
    }

    private function getArtifactLinkFieldId() {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['type'] == 'art_link') {
                return $field['field_id'];
            }
        }
        throw new \Exception('No artifact link field for tracker');
    }

    private function getListValueIdByLabel(array $field, $field_value_label) {
        foreach ($field['values'] as $value) {
            if ($value['label'] == $field_value_label) {
                return $value['id'];
            }
        }
    }

    private function getFieldByLabel($field_label) {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['label'] == $field_label) {
                return $field;
            }
        }
    }
}
