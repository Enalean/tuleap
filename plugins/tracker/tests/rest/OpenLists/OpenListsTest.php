<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Tests\REST\OpenLists;

use Tuleap\Tracker\Tests\REST\TrackerBase;

final class OpenListsTest extends TrackerBase
{
    public function testGetArtifactOpenListsValues(): void
    {
        $artifact_id = $this->open_list_artifact_id;

        $response = $this->getResponse(
            $this->request_factory->createRequest('GET', "artifacts/$artifact_id")
        );

        self::assertSame(200, $response->getStatusCode());

        $json = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        $nb_assertion = 0;
        foreach ($json['values'] as $field_values) {
            if ($field_values['label'] === 'StaticOpenList') {
                self::assertEqualsCanonicalizing(
                    ['value01', 'value03'],
                    $field_values['bind_value_ids'],
                );
                $nb_assertion++;
            } elseif ($field_values['label'] === 'UserOpenList') {
                self::assertEqualsCanonicalizing(
                    ['Test User 1 (rest_api_tester_1)'],
                    $field_values['bind_value_ids'],
                );
                $nb_assertion++;
            } elseif ($field_values['label'] === 'UGroupOpenList') {
                self::assertEqualsCanonicalizing(
                    ['Project members'],
                    $field_values['bind_value_ids'],
                );
                $nb_assertion++;
            }
        }

        if ($nb_assertion < 3) {
            self::fail('Not all open list fields have been checked.');
        }
    }
}
