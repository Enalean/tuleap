<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../../../../include/workflow/PostAction/CIBuild/Transition_PostAction_CIBuild.class.php');

class Transition_PostAction_CIBuildTest extends TuleapTestCase {

    public function itCallsDeleteFunctionInDaoWhenDeleteisProcess() {

        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://www.example.com';
        $condendi_request = aRequest()->with('remove_postaction', array(123 => 1))->build();

        $ci_build_dao = mock('Transition_PostAction_CIBuildDao');

        $post_action_ci_build = partial_mock('Transition_PostAction_CIBuild', array('getDao'), array($transition, $id, $job_url));
        stub($post_action_ci_build)->getDao()->returns($ci_build_dao);

        expect($ci_build_dao)->deletePostAction($id)->once();
        $post_action_ci_build->process($condendi_request);
    }

    public function itIsNotDefinedWhenJobUrlIsEmpty() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = null;

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url);
        $this->assertFalse($post_action_ci_build->isDefined());
    }

    public function itIsDefinedWhenJobUrlIsFilled() {
        $transition       = mock('Transition');
        $id               = 123;
        $job_url          = 'http://example.com/job';

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url);
        $this->assertTrue($post_action_ci_build->isDefined());
    }
}
?>
