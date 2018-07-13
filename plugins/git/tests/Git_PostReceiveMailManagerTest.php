<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of tuleap.
 *
 * tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

class Git_PostReceiveMailManagerTest extends TuleapTestCase
{

    public function testRemoveMailByRepository()
    {
        $prm = partial_mock('Git_PostReceiveMailManager', array('addMail', '_getDao','_getGitDao', '_getGitRepository'));
        $dao = mock('Git_PostReceiveMailDao');
        $prm->dao = $dao;

        $repo = mock('GitRepository');

        $backend = mock('GitBackend');
        $repo->SetReturnValue('getBackend', $backend);

        $prm->dao->setReturnValue('removeNotification', True);

        $repo->expectOnce('loadNotifiedMails');
        $backend->expectOnce('changeRepositoryMailingList');

        $prm->removeMailByRepository($repo, "codendiadm@codendi.org");
    }
}
