<?php
/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Reference\Creation;

use CSRFSynchronizerToken;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Service\ServiceDao;
use Tuleap\Reference\Nature;
use Tuleap\Reference\NatureCollection;
use Tuleap\Test\Builders\UserTestBuilder;

/**
 * @psalm-immutable
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreateReferencePresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    public function testItShouldBuildAPresenterForPlatformAdministrator(): void
    {
        $service_dao = $this->createMock(ServiceDao::class);
        $service_dao->method('searchByProjectId')->willReturn([['label' => 'Tracker from DB', 'short_name' => 'tracker']]);

        $nature_list = new NatureCollection();
        $git_nature  = new Nature('git', '', 'Git', true);
        $nature_list->addNature('git', $git_nature);
        $tracker_nature = new Nature('tracker', '', 'Tracker', true);
        $nature_list->addNature('tracker', $tracker_nature);
        $url = '/project/admin/reference.php?group_id=101';

        $builder   = new CreateReferencePresenterBuilder($service_dao);
        $presenter = $builder->buildReferencePresenter(101, $nature_list->getNatures(), true, $url, $this->createMock(CSRFSynchronizerToken::class), UserTestBuilder::buildWithId(103));

        self::assertEquals([new ServiceReferencePresenter('tracker', 'Tracker from DB')], $presenter->services_reference);
        self::assertEquals([new NatureReferencePresenter('git', $git_nature), new NatureReferencePresenter('tracker', $tracker_nature)], $presenter->natures);
    }

    public function testItShouldBuildAPresenter(): void
    {
        $service_dao = $this->createMock(ServiceDao::class);
        $service_dao->method('searchById')->willReturn([
            ['label' => 'Tracker from DB', 'short_name' => 'tracker'],
        ]);

        $nature_list = new NatureCollection();
        $git_nature  = new Nature('git', '', 'Git', false);
        $nature_list->addNature('git', $git_nature);
        $url = '/project/admin/reference.php?group_id=101';

        $builder   = new CreateReferencePresenterBuilder($service_dao);
        $presenter = $builder->buildReferencePresenter(101, $nature_list->getNatures(), false, $url, $this->createMock(CSRFSynchronizerToken::class), UserTestBuilder::buildWithId(103));

        self::assertEquals([ ], $presenter->services_reference);
        self::assertEquals([], $presenter->natures);
    }
}
