<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Tests\REST\Artifacts;

use REST_TestDataBuilder;
use Tuleap\Tracker\Tests\REST\TrackerBase;

require_once __DIR__ . '/../bootstrap.php';

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactsTest extends TrackerBase
{
    public function testGetArtifactWithMinimalStructure(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id")
        );

        $this->assertArtifactWithMinimalStructure($response);
    }

    public function testGetArtifactWithCompleteStructure(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id?tracker_structure_format=complete")
        );

        $this->assertArtifactWithCompleteStructure($response);
    }

    public function testGetArtifactWithMinimalStructureWithUserRESTReadOnlyAdmin(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertArtifactWithMinimalStructure($response);
    }

    public function testGetArtifactWithCompleteStructureWithUserRESTReadOnlyAdmin(): void
    {
        $artifact_id = end($this->base_artifact_ids);

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id?tracker_structure_format=complete"),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );

        $this->assertArtifactWithCompleteStructure($response);
    }

    private function assertArtifactWithMinimalStructure(\Psr\Http\Message\ResponseInterface $response): void
    {
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->base_tracker_id, $json['tracker']['id']);
        $this->assertFalse(isset($json['tracker']['fields']));
    }

    private function assertArtifactWithCompleteStructure(\Psr\Http\Message\ResponseInterface $response): void
    {
        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($this->base_tracker_id, $json['tracker']['id']);
        $this->assertTrue(isset($json['tracker']['fields']));
    }

    public function testItSavesTheUserDefaultFormatIfTheTextFieldFormatIsInvalidWhenTheArtifactIsCreated(): int
    {
        $description_field_id = $this->getAUsedFieldId($this->tracker_all_fields_tracker_id, 'description');
        $title_field_id       = $this->getAUsedFieldId($this->tracker_all_fields_tracker_id, 'title');
        $payload              = [
            'tracker' => ['id' => $this->tracker_all_fields_tracker_id],
            'values'  => [
                [
                    'field_id' => $title_field_id,
                    'value'    => 'Text field format test title',
                ],
                [
                    'field_id' => $description_field_id,
                    'value'    => [
                        'content' => 'Straight Outta Compton',
                        'format'  => 'gang',
                    ],
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('POST', 'artifacts')->withBody($this->stream_factory->createStream(json_encode($payload)))
        );

        self::assertEquals(201, $response->getStatusCode());

        $created_artifact_id = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)['id'];

        $this->assertSavedTextFieldFormatIsUserDefaultFormat($created_artifact_id);

        return $created_artifact_id;
    }

    /**
     * @depends testItSavesTheUserDefaultFormatIfTheTextFieldFormatIsInvalidWhenTheArtifactIsCreated
     */
    public function testItSavesTheUserDefaultFormatIfTheTextFieldFormatIsInvalidWhenTheArtifactIsUpdated(
        int $created_artifact_id,
    ): void {
        $description_field_id = $this->getAUsedFieldId($this->tracker_all_fields_tracker_id, 'description');

        $payload = [
            'tracker' => ['id' => $this->tracker_all_fields_tracker_id],
            'values'  => [
                [
                    'field_id' => $description_field_id,
                    'value'    => [
                        'content' => "100 Miles And Runnin'",
                        'format'  => 'whololo',
                    ],
                ],
            ],
        ];

        $response = $this->getResponse(
            $this->request_factory->createRequest('PUT', 'artifacts/' . $created_artifact_id)->withBody($this->stream_factory->createStream(json_encode($payload)))
        );

        self::assertEquals(200, $response->getStatusCode());
        $this->assertSavedTextFieldFormatIsUserDefaultFormat($created_artifact_id);
    }

    private function assertSavedTextFieldFormatIsUserDefaultFormat(int $concerned_artifact_id): void
    {
        $artifact_request = $this->request_factory->createRequest('GET', 'artifacts/' . $concerned_artifact_id);
        $artifact         = json_decode($this->getResponse($artifact_request)->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $description_field = array_filter(
            $artifact['values'],
            static function (array $value): bool {
                return $value['label'] === 'Description';
            }
        );

        // Default user format = commonmark
        $description_field_format  = array_column($description_field, 'format');
        $description_field_content = array_column($description_field, 'commonmark');

        self::assertEquals('html', $description_field_format[0]);
        self::assertNotNull($description_field_content[0]);
    }
}
