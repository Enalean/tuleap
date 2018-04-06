<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Tracker\Tests\REST\ArtifactsDeletion;

use Guzzle\Http\Exception\ClientErrorResponseException;
use RestBase;

class ArtifactsDeletionTest extends RestBase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testDeleteArtifacts()
    {
        $response = $this->performArtifactDeletion(3);

        $this->assertEquals($response->getStatusCode(), 200);

        $this->assertEquals(
            $response->getHeader('x-ratelimit-limit')->toArray()[0],
            "1"
        );

        $this->assertEquals(
            $response->getHeader('x-ratelimit-remaining')->toArray()[0],
            "0"
        );
    }

    /**
     * @depends testDeleteArtifacts
     */
    public function itThrowsAnErrorWhenUserReachesTheLimitOfDeletedArtifacts()
    {
        $this->expectExceptionCode(429);
        $this->expectExceptionMessage('Too many requests: The limit of artifacts deletions has been reached for the previous 24 hours.');

        $this->performArtifactDeletion(4);
    }

    private function performArtifactDeletion($artifact_id)
    {
        $url = "artifacts/$artifact_id";

        return $this->getResponse(
            $this->client->delete($url)
        );
    }
}
