<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

use DateTimeImmutable;
use PFUser;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\BaselinesPage;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\GlobalLanguageMock;

require_once __DIR__ . "/../bootstrap.php";

final class BaselinesPageRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testBuild(): void
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03');
        self::assertInstanceOf(DateTimeImmutable::class, $date);

        $representation = BaselinesPageRepresentation::build(
            new BaselinesPage(
                [
                    BaselineFactory::one()
                        ->id(3)
                        ->name('Represented baseline')
                        ->artifact(BaselineArtifactFactory::one()->id(13)->build())
                        ->snapshotDate($date)
                        ->author(UserProxy::fromUser(new PFUser(['user_id' => 22])))
                        ->build(),

                ],
                10,
                3,
                233
            )
        );

        $expected_representation = new BaselinesPageRepresentation(
            [
                new BaselineRepresentation(
                    3,
                    'Represented baseline',
                    13,
                    '2019-03-21T14:47:03+01:00',
                    22
                ),
            ],
            233
        );
        self::assertEquals($expected_representation, $representation);
    }
}
