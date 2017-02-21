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

namespace Tuleap\Git\Gitolite\SSHKey;

use TuleapTestCase;

require_once __DIR__ .'/../../../bootstrap.php';

class ManagementDetectorTest extends TuleapTestCase
{
    public function itCanNotManageAuthorizedKeysFileIfGitolite3IsNotUsed()
    {
        $version_detector     = mock('Tuleap\Git\Gitolite\VersionDetector');
        stub($version_detector)->isGitolite3()->returns(false);
        $global_parameter_dao = mock('Tuleap\Git\GlobalParameterDao');

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao);

        $this->assertFalse($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }

    public function itUsesGlobalParameterToDetermineIfTuleapManagesAuthorizedKeysFile()
    {
        $version_detector     = mock('Tuleap\Git\Gitolite\VersionDetector');
        stub($version_detector)->isGitolite3()->returns(true);
        $global_parameter_dao = mock('Tuleap\Git\GlobalParameterDao');

        $management_detector = new ManagementDetector($version_detector, $global_parameter_dao);

        stub($global_parameter_dao)->isAuthorizedKeysFileManagedByTuleap()->returns(true);
        $this->assertTrue($management_detector->isAuthorizedKeysFileManagedByTuleap());

        stub($global_parameter_dao)->isAuthorizedKeysFileManagedByTuleap()->returns(false);
        $this->assertTrue($management_detector->isAuthorizedKeysFileManagedByTuleap());
    }
}
