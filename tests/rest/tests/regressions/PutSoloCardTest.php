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
/**
 * PUT /cards/:id cannot update solo card
 *
 * In the context of a sprint Cardwall, if we change through direct edition, the
 * value of the field "Story Point" of a backlog "User Story" ,we receive the
 * error "column_id is required", after submitting this sprint.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=6430
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('Regressions')]
class Regressions_PutSoloCardTest extends RestBase
{
    public function testItEditSoloCardLabel()
    {
        $stories     = $this->getArtifactIdsIndexedByTitle('private-member', 'story');
        $planning_id = $this->getSprintPlanningId();

        $put      = json_encode(
            [
                'label'     => 'Whatever',
                'column_id' => null,
                'values'    => [],
            ]
        );
        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'cards/' . $planning_id . '_' . $stories['Story 1'])
                ->withBody($this->stream_factory->createStream($put))
        );
        $this->assertEquals($response->getStatusCode(), 200);
    }

    private function getSprintPlanningId()
    {
        $response          = $this->getResponse($this->request_factory->createRequest('GET', "projects/$this->project_private_member_id/plannings"));
        $project_plannings = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($project_plannings as $planning) {
            if ($planning['label'] == 'Sprint Planning') {
                return $planning['id'];
            }
        }
    }
}
