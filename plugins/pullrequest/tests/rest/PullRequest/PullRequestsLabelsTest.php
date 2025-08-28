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

use Tuleap\REST\RESTTestDataBuilder;
use Tuleap\REST\RestBase;

require_once dirname(__FILE__) . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('PullRequest')]
final class PullRequestsLabelsTest extends RestBase
{
    public function testOPTIONS(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'pull_requests/1/labels'));

        $this->assertEquals(['OPTIONS', 'GET', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testOPTIONSWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('OPTIONS', 'pull_requests/1/labels'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(['OPTIONS', 'GET', 'PATCH'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testGETLabel(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'pull_requests/1/labels'));

        $this->assertGETLabel($response);
    }

    public function testGETLabelWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'pull_requests/1/labels'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertGETLabel($response);
    }

    private function assertGETLabel(\Psr\Http\Message\ResponseInterface $response): void
    {
        $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals([], $content['labels']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGETLabel')]
    public function testPATCHAddUnknownLabel(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'pull_requests/1/labels')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'add' => [
                        ['label' => 'Emergency Fix'],
                    ],
                ]
            )))
        );
        $this->assertEquals($response->getStatusCode(), 200);

        $response = json_decode($this->getResponse($this->request_factory->createRequest('GET', 'pull_requests/1/labels'))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testGETLabel')]
    public function testPATCHWithReadOnlyAdmin(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('PATCH', 'pull_requests/1/labels')->withBody($this->stream_factory->createStream(json_encode(
                [
                    'add' => [
                        ['label' => 'Emergency Fix'],
                    ],
                ]
            ))),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPATCHAddUnknownLabel')]
    public function testNewLabelIsAddedToProject()
    {
        $project_id = $this->getProjectId('test-git');
        $response   = json_decode($this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/labels"))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testNewLabelIsAddedToProject')]
    public function testPATCHRemoveLabel()
    {
        $response  = json_decode($this->getResponse($this->request_factory->createRequest('GET', 'pull_requests/1/labels'))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $label_ids = array_map(
            function ($label) {
                return ['id' => $label['id']];
            },
            $response['labels']
        );

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', 'pull_requests/1/labels')->withBody($this->stream_factory->createStream(json_encode(
            [
                'remove' => $label_ids,
            ]
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = json_decode($this->getResponse($this->request_factory->createRequest('GET', 'pull_requests/1/labels'))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals([], $response['labels']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testPATCHRemoveLabel')]
    public function testRemovedLabelsAreNotRemovedInProject()
    {
        $project_id = $this->getProjectId('test-git');
        $response   = json_decode($this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/labels"))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testRemovedLabelsAreNotRemovedInProject')]
    public function testPATCHAddProjectLabel()
    {
        $project_id     = $this->getProjectId('test-git');
        $response       = json_decode($this->getResponse($this->request_factory->createRequest('GET', "projects/$project_id/labels"))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $expected_label = $response['labels'][0];

        $response = $this->getResponse($this->request_factory->createRequest('PATCH', 'pull_requests/1/labels')->withBody($this->stream_factory->createStream(json_encode(
            [
                'add' => [
                    ['id' => $expected_label['id']],
                ],
            ]
        ))));
        $this->assertEquals($response->getStatusCode(), 200);

        $response = json_decode($this->getResponse($this->request_factory->createRequest('GET', 'pull_requests/1/labels'))->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(1, $response['labels']);
        $this->assertEquals('Emergency Fix', $response['labels'][0]['label']);
    }
}
