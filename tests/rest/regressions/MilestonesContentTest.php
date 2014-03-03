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

require_once dirname(__FILE__).'/../../lib/autoload.php';

/**
 * When you move an artifact from the release plan back to the product backlog and Submit the changes an error is generated
 *
 * @group Regressions
 */
class Regressions_MilestonesContentTest extends RestBase {

    private $project_trackers;
    private $one_epic;
    private $another_epic;
    private $product;
    private $release;

    public function testItCanMoveBackFromReleaseBacklogToProductBacklog() {
        $this->getResponse($this->client->put('milestones/'.$this->release['id'].'/content', null, json_encode(array($this->one_epic['id']))));

        $this->assertEquals($this->getMilestoneContentIds($this->release['id']), array($this->one_epic['id']));
        $this->assertEquals($this->getMilestoneContentIds($this->product['id']), array($this->one_epic['id'], $this->another_epic['id']));
        $this->assertEquals($this->getMilestoneBacklogIds($this->product['id']), array($this->another_epic['id']));
    }

    private function getMilestoneBacklogIds($id) {
        return $this->getIds("milestones/$id/backlog");
    }

    private function getMilestoneContentIds($id) {
        return $this->getIds("milestones/$id/content");
    }

    private function getIds($route) {
        $ids = array();
        foreach ($this->getResponse($this->client->get($route))->json() as $item) {
            $ids[] = $item['id'];
        }
        return $ids;
    }

    public function setUp() {
        parent::setUp();
        $this->cacheProjectTrackers(TestDataBuilder::PROJECT_PBI_ID);
        $this->createBacklog();
        $this->createProductAndRelease();
        $this->assignEpicsToProductAndRelease();
    }

    private function createBacklog() {
        $this->one_epic     = $this->createEpic('One Epic');
        $this->another_epic = $this->createEpic('Another Epic');
    }

    private function createProductAndRelease() {
        $this->product = $this->createProduct("Widget");
        $this->release = $this->createRelease("1.0");
        $this->getResponse($this->client->put('milestones/'.$this->product['id'].'/milestones', null, json_encode(array($this->release['id']))));
    }

    private function assignEpicsToProductAndRelease() {
        $this->getResponse($this->client->put('milestones/'.$this->product['id'].'/content', null, json_encode(array($this->one_epic['id'], $this->another_epic['id']))));
        $this->getResponse($this->client->put('milestones/'.$this->release['id'].'/content', null, json_encode(array($this->one_epic['id'], $this->another_epic['id']))));
    }

    private function createEpic($summary) {
        return $this->createArtifact(
            $this->project_trackers['epic'],
            array(
               $this->getSubmitTextValue($this->project_trackers['epic'], 'Title', $summary),
               $this->getSubmitListValue($this->project_trackers['epic'], 'Status', 'Not Started')
            )
        );
    }

    private function createProduct($name) {
        return $this->createArtifact(
            $this->project_trackers['product'],
            array(
               $this->getSubmitTextValue($this->project_trackers['product'], 'Name', $name),
            )
        );
    }

    private function createRelease($release) {
        return $this->createArtifact(
            $this->project_trackers['releases'],
            array(
               $this->getSubmitTextValue($this->project_trackers['releases'], 'Version Number', $release),
            )
        );
    }

    private function createArtifact(array $tracker, array $values) {
        $post = json_encode(array(
            'tracker' => array(
                'id'  => $tracker['id'],
                'uri' => 'whatever'
            ),
            'values' => $values,
        ));
        return $this->getResponse($this->client->post('artifacts', null, $post))->json();
    }

    private function getSubmitTextValue(array $tracker, $field_label, $field_value) {
        $field_def = $this->getFieldByLabel($tracker, $field_label);
        return array(
            'field_id' => $field_def['field_id'],
            'value'    => $field_value,
        );
    }

    private function getSubmitListValue(array $tracker, $field_label, $field_value_label) {
        $field_def = $this->getFieldByLabel($tracker, $field_label);
        return array(
            'field_id'       => $field_def['field_id'],
            'bind_value_ids' => array(
                $this->getListValueIdByLabel($field_def, $field_value_label)
            ),
        );
    }

    private function getListValueIdByLabel(array $field, $field_value_label) {
        foreach ($field['values'] as $value) {
            if ($value['label'] == $field_value_label) {
                return $value['id'];
            }
        }
    }

    private function getFieldByLabel(array $tracker, $field_label) {
        foreach ($tracker['fields'] as $field) {
            if ($field['label'] == $field_label) {
                return $field;
            }
        }
    }

    private function cacheProjectTrackers($project_id) {
        $project_trackers = $this->getResponse($this->client->get("projects/$project_id/trackers"))->json();
        foreach ($project_trackers as $tracker) {
            $this->project_trackers[strtolower($tracker['item_name'])] = $tracker;
        }
    }

    protected function getResponse($request) {
        return $this->getResponseByToken(
            $this->getTokenForUserName(TestDataBuilder::TEST_USER_1_NAME),
            $request
        );
    }
}