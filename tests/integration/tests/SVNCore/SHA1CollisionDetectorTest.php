<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\SVNCore;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class SHA1CollisionDetectorTest extends TestIntegrationTestCase
{
    public function testDetectsKnownCollision(): void
    {
        $sha1_collision_detector = new SHA1CollisionDetector();
        $colliding_resource      = fopen(__DIR__ . '/_fixtures/tuleap-shattered.pdf', 'rb');

        $this->assertTrue($sha1_collision_detector->isColliding($colliding_resource));

        fclose($colliding_resource);
    }

    public function testDoesNotDetectACollisionOnANonCollidingResource(): void
    {
        $sha1_collision_detector = new SHA1CollisionDetector();
        $non_colliding_resource  = fopen('php://memory', 'rb+');
        fwrite($non_colliding_resource, 'SHA-1 is dead!');
        rewind($non_colliding_resource);

        $this->assertFalse($sha1_collision_detector->isColliding($non_colliding_resource));

        fclose($non_colliding_resource);
    }
}
