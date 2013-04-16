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

require_once dirname(__FILE__).'/../../../tracker/tests/Tracker/CrossSearch/SearchContentViewTest.php';
require_once dirname(__FILE__).'/../../include/Planning/Planning.class.php';
require_once dirname(__FILE__).'/../../include/Planning/SearchContentView.class.php';
require_once dirname(__FILE__).'/../../../../tests/simpletest/common/include/builders/aMockTemplateRenderer.php';

class Planning_SearchContentViewTest extends Tracker_CrossSearch_SearchContentViewTest {

    public function itCanGenerateALinkToAddNewBacklogRootItems() {
        $report = mock('Tracker_Report');
        $criteria = array();
        $tree_of_artifacts = new TreeNode();
        $artifact_factory = mock('Tracker_ArtifactFactory');
        $form_element_factory = mock('Tracker_FormElementFactory');
        $user = mock('PFUser');
        $backlog_actions_presenter = mock('Planning_BacklogActionsPresenter');
        $planning = mock('Planning');
        $planning_redirect_param = '';
        
        $backlog_actions_markup = 'Some backlog actions';
        $renderer = aMockTemplateRenderer()->build();
        
        $view = new Planning_SearchContentView($report, $criteria, $tree_of_artifacts, $artifact_factory, $form_element_factory, $user, $backlog_actions_presenter, $planning, $planning_redirect_param);
        $view->setRenderer($renderer);
        
        stub($renderer)->renderToString('backlog-actions', $backlog_actions_presenter)->returns($backlog_actions_markup);
        
        $output = $view->fetch();
        
        $this->assertStringContains($output, $backlog_actions_markup);
    }
}

?>
