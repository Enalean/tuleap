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
 
require_once 'Presenter.class.php';
require_once 'IndexPresenter.class.php';
require_once 'PlanningFactory.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_ArtifactFactory.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/Tracker/Artifact/Tracker_Artifact.class.php';

class Planning_Controller {

    /**
     * @var Renderer
     */
    private $renderer;
    
    /**
     * @var Tracker_Artifact
     */
    private $artifact;
    
    function __construct(Codendi_Request $request, Tracker_ArtifactFactory $artifact_factory, PlanningFactory $planning_factory) {
        $aid = $request->get('aid');
        $this->group_id = $request->get('group_id');
        $this->artifact = $artifact_factory->getArtifactById($aid);
        $this->renderer = new MustacheRenderer(dirname(__FILE__).'/../../templates');
        $this->planning_factory = $planning_factory;
    }

    function display() {
        $presenter = new Planning_Presenter($this->artifact);
        $this->render('release-view', $presenter);
    }

    private function render($template_name, $presenter) {
        echo $this->renderer->render($template_name, $presenter);
    }
    
    public function index() {
        $presenter = new Planning_IndexPresenter ($this->planning_factory, $this->group_id);
        $this->render('index', $presenter);
    }
}
?>
