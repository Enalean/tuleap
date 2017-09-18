<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

require_once __DIR__ . '/../bootstrap.php';

class CrossReferenceCreatorTest extends \TuleapTestCase
{
    public function itCreatesReferenceOnce()
    {
        $cross_reference_dao = mock('CrossReferenceDao');
        $rest_reference_dao  = mock('Tuleap\\Bugzilla\\Reference\\RESTReferenceCreator');

        $cross_reference_creator = new CrossReferenceCreator($cross_reference_dao, $rest_reference_dao);

        stub($cross_reference_dao)->fullReferenceExistInDb()->returnsAt(0, false);
        stub($cross_reference_dao)->fullReferenceExistInDb()->returnsAt(1, true);

        $cross_reference_dao->expectOnce('createDbCrossRef');
        $rest_reference_dao->expectOnce('create');

        $cross_reference    = mock('CrossReference');
        $bugzilla_reference = mock('Tuleap\\Bugzilla\\Reference\\Reference');

        $cross_reference_creator->create($cross_reference, $bugzilla_reference);
        $cross_reference_creator->create($cross_reference, $bugzilla_reference);
    }
}
