<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Bugzilla;

use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferencesDao;

require_once __DIR__ . '/../bootstrap.php';

final class CrossReferenceCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CrossReferencesDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $cross_reference_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Reference\RESTReferenceCreator
     */
    private $rest_reference_dao;

    private CrossReferenceCreator $cross_reference_creator;

    public function setUp(): void
    {
        parent::setUp();

        $this->cross_reference_dao = $this->createMock(CrossReferencesDao::class);
        $this->rest_reference_dao  = $this->createMock(\Tuleap\Bugzilla\Reference\RESTReferenceCreator::class);

        $this->cross_reference_creator = new CrossReferenceCreator(
            $this->cross_reference_dao,
            $this->rest_reference_dao
        );
    }

    public function testItCreatesReference(): void
    {
        $this->cross_reference_dao->method('fullReferenceExistInDb')->willReturn(false);

        $this->cross_reference_dao->expects(self::once())->method('createDbCrossRef');
        $this->rest_reference_dao->expects(self::once())->method('create');

        $cross_reference    = $this->createMock(CrossReference::class);
        $bugzilla_reference = $this->createMock(\Tuleap\Bugzilla\Reference\Reference::class);

        $this->cross_reference_creator->create($cross_reference, $bugzilla_reference);
    }

    public function testItDoesNotCreateReferenceIfAlreadyExisting(): void
    {
        $this->cross_reference_dao->method('fullReferenceExistInDb')->willReturn(true);

        $this->cross_reference_dao->expects(self::never())->method('createDbCrossRef');
        $this->rest_reference_dao->expects(self::never())->method('create');

        $cross_reference    = $this->createMock(CrossReference::class);
        $bugzilla_reference = $this->createMock(\Tuleap\Bugzilla\Reference\Reference::class);

        $this->cross_reference_creator->create($cross_reference, $bugzilla_reference);
    }
}
