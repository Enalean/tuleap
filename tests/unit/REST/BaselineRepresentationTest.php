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

require_once __DIR__ . '/../bootstrap.php';

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tuleap\Baseline\Factory\BaselineArtifactFactory;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\GlobalLanguageMock;

class BaselineRepresentationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    public function testFromBaseline()
    {
        $baseline = BaselineFactory::one()
            ->id(3)
            ->name('Matching baseline')
            ->artifact(BaselineArtifactFactory::one()->id(13)->build())
            ->snapshotDate(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', '2019-03-21 14:47:03'))
            ->author(new PFUser(['user_id' => 22]))
            ->build();

        $representation = BaselineRepresentation::fromBaseline($baseline);

        $this->assertEquals(3, $representation->id);
        $this->assertEquals('Matching baseline', $representation->name);
        $this->assertEquals(13, $representation->artifact_id);
        $this->assertEquals(22, $representation->author_id);
        $this->assertEquals('2019-03-21T14:47:03+01:00', $representation->snapshot_date);
    }
}
