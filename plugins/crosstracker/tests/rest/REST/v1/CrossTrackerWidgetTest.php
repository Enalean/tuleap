<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use Psl\Json;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tuleap\CrossTracker\TestBase;
use Tuleap\REST\RESTTestDataBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CrossTrackerWidgetTest extends TestBase
{
    private const UUID_PATTERN = '/^[0-9a-fA-F]{8}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{4}\b-[0-9a-fA-F]{12}$/';

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->getEpicArtifactIds();
    }

    public function testGetId(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_widget/3'));
        self::assertSame(200, $response->getStatusCode());
        $this->assertGetIdWidget($response);
    }

    public function testGetIdForReadOnlyUser(): void
    {
        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', 'crosstracker_widget/3'),
            RESTTestDataBuilder::TEST_BOT_USER_NAME
        );

        self::assertSame(200, $response->getStatusCode());
        $this->assertGetIdWidget($response);
    }

    private function assertGetIdWidget(ResponseInterface $response): void
    {
        $json_response = Json\decode($response->getBody()->getContents());
        self::assertIsArray($json_response);
        self::assertArrayHasKey('queries', $json_response);
        /** @var list<array{tql_query: string, title: string, description: string, id: string}> $queries */
        $queries = $json_response['queries'];
        self::assertCount(1, $queries);
        self::assertSame('', $queries[0]['tql_query']);
        self::assertSame('Title 1', $queries[0]['title']);
        self::assertSame('Description', $queries[0]['description']);
        self::assertMatchesRegularExpression(self::UUID_PATTERN, $queries[0]['id']);
    }

    public function testItThrowsAnExceptionWhenWidgetIsNotFound(): void
    {
        $response = $this->getResponse($this->request_factory->createRequest('GET', 'crosstracker_widget/100'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testYouCantAccessPersonalWidgetOfAnOtherUser(): void
    {
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('GET', 'crosstracker_widget/4'));

        self::assertSame(404, $response->getStatusCode());
    }

    public function testYouCantAccessProjectWidgetOfProjectYouCantSee(): void
    {
        $response = $this->getResponseForNonProjectMember($this->request_factory->createRequest('GET', 'crosstracker_widget/' . self::WIDGET_ID));

        self::assertSame(404, $response->getStatusCode());
    }

    private function getResponseForNonProjectMember(RequestInterface $request): ResponseInterface
    {
        return $this->getResponse($request, RESTTestDataBuilder::TEST_USER_4_NAME);
    }
}
