<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Tuleap\REST\CardsBase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
#[\PHPUnit\Framework\Attributes\Group('CardsTests')]
class CardsTest extends CardsBase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function testOPTIONSCards()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'cards'));
        self::assertEqualsCanonicalizing(['OPTIONS'], explode(', ', $response->getHeaderLine('Allow')));
    }

    public function testPUTCardsWithId()
    {
        $card_id        = REST_TestDataBuilder::PLANNING_ID . '_' . $this->story_artifact_ids[1];
        $test_label     = 'Ieatlaughingcow';
        $test_column_id = 2;

        // Keep original values

        $original_card = $this->findCardInCardwall(
            json_decode(
                $this->getResponse(
                    $this->request_factory->createRequest(
                        'GET',
                        'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall'
                    )
                )->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
            $card_id
        );

        $response_put = $this->getResponse(
            $this->request_factory->createRequest('PUT', "cards/$card_id")
                ->withBody($this->stream_factory->createStream(
                    '
                            {
                                "label": "' . $test_label . '",
                                "column_id": ' . $test_column_id . ',
                                "values": []
                            }
                        '
                ))
        );
        $this->assertEquals($response_put->getStatusCode(), 200);

        $card = $this->findCardInCardwall(
            json_decode(
                $this->getResponse(
                    $this->request_factory->createRequest(
                        'GET',
                        'milestones/' . $this->sprint_artifact_ids[1] . '/cardwall'
                    )
                )->getBody()->getContents(),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
            $card_id
        );

        $this->assertEquals($card['label'], $test_label);
        $this->assertEquals($card['column_id'], $test_column_id);

        // Restore original values
        $this->getResponse(
            $this->request_factory->createRequest('PUT', "cards/$card_id")
                ->withBody($this->stream_factory->createStream('
                    {
                        "label": "' . $original_card['label'] . '",
                        "column_id": ' . $original_card['column_id'] . ',
                        "values": []
                    }
                '))
        );
    }

    public function testPUTCardsForReadOnlyUser(): void
    {
        $card_id      = REST_TestDataBuilder::PLANNING_ID . '_' . $this->story_artifact_ids[1];
        $response_put = $this->getResponse(
            $this->request_factory->createRequest(
                'PUT',
                "cards/$card_id"
            )->withBody(
                $this->stream_factory->createStream(
                    '
                {
                    "label": "Ieatlaughingcow",
                    "column_id": 2,
                    "values": []
                } '
                )
            ),
            REST_TestDataBuilder::TEST_BOT_USER_NAME
        );
        $this->assertEquals(404, $response_put->getStatusCode());
    }

    private function findCardInCardwall($cardwall, $id)
    {
        foreach ($cardwall['swimlanes'] as $swimlane) {
            foreach ($swimlane['cards'] as $card) {
                if ($card['id'] == $id) {
                    return $card;
                }
            }
        }
    }

    public function testOPTIONSCardsWithId()
    {
        $response = $this->getResponse($this->request_factory->createRequest('OPTIONS', 'cards/' . $this->sprint_artifact_ids[1] . '_' . $this->story_artifact_ids[1]));
        self::assertEqualsCanonicalizing(['OPTIONS', 'PUT'], explode(', ', $response->getHeaderLine('Allow')));
    }
}
