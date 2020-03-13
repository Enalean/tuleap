<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

namespace Tuleap\PullRequest;

use Guzzle\Http\Message\Response;
use REST_TestDataBuilder;
use RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

/**
 * @group PullRequest
 */
final class PullRequestsLabelsTest extends RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->client->options('pull_requests/1/labels'));

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testOPTIONSWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->options('pull_requests/1/labels'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(array('OPTIONS', 'GET', 'PATCH'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETLabel(): void
    {
        $response = $this->getResponse($this->client->get('pull_requests/1/labels'));

        $this->assertGETLabel($response);
    }

    public function testGETLabelWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->get('pull_requests/1/labels'),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETLabel($response);
    }

    private function assertGETLabel(Response $response): void
    {
        $content = $response->json();

        $this->assertEquals(array(), $content['labels']);
    }

    /**
     * @depends testGETLabel
     */
    public function testPATCHAddUnknownLabel(): void
    {
        $response = $this->getResponse(
            $this->client->patch(
                'pull_requests/1/labels',
                null,
                json_encode(
                    array(
                        'add' => array(
                            array('label' => 'Emergency Fix')
                        )
                    )
                )
            )
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('pull_requests/1/labels'))->json();
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }

    /**
     * @depends testGETLabel
     */
    public function testPATCHWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->client->patch(
                'pull_requests/1/labels',
                null,
                json_encode(
                    array(
                        'add' => array(
                            array('label' => 'Emergency Fix')
                        )
                    )
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * @depends testPATCHAddUnknownLabel
     */
    public function testNewLabelIsAddedToProject()
    {
        $project_id = $this->getProjectId('test-git');
        $response = $this->getResponse($this->client->get("projects/$project_id/labels"))->json();
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }

    /**
     * @depends testNewLabelIsAddedToProject
     */
    public function testPATCHRemoveLabel()
    {
        $response = $this->getResponse($this->client->get('pull_requests/1/labels'))->json();
        $label_ids = array_map(
            function ($label) {
                return array('id' => $label['id']);
            },
            $response['labels']
        );

        $response = $this->getResponse($this->client->patch('pull_requests/1/labels', null, json_encode(
            array(
                'remove' => $label_ids
            )
        )));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('pull_requests/1/labels'))->json();
        $this->assertEquals(array(), $response['labels']);
    }

    /**
     * @depends testPATCHRemoveLabel
     */
    public function testRemovedLabelsAreNotRemovedInProject()
    {
        $project_id = $this->getProjectId('test-git');
        $response = $this->getResponse($this->client->get("projects/$project_id/labels"))->json();
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }

    /**
     * @depends testRemovedLabelsAreNotRemovedInProject
     */
    public function testPATCHAddProjectLabel()
    {
        $project_id = $this->getProjectId('test-git');
        $response = $this->getResponse($this->client->get("projects/$project_id/labels"))->json();
        $expected_label = $response['labels'][0];

        $response = $this->getResponse($this->client->patch('pull_requests/1/labels', null, json_encode(
            array(
                'add' => array(
                    array('id' => $expected_label['id'])
                )
            )
        )));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = $this->getResponse($this->client->get('pull_requests/1/labels'))->json();
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }
}
