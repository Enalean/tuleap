<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

require_once 'common/mvc2/PluginController.class.php';

/**
 * Controller for a campaign resource
 */
class Testing_Campaign_CampaignController extends MVC2_PluginController {

    /** @var Testing_Campaign_CampaignPresenterCollectionFactory */
    private $presenter_collection_factory;

    public function __construct(Codendi_Request $request, Testing_Campaign_CampaignPresenterCollectionFactory $presenter_collection_factory) {
        parent::__construct('testing', $request);
        $this->presenter_collection_factory = $presenter_collection_factory;
    }

    /**
     * Fallback to prevent us implement dummy methods for the poc
     */
    public function __call($name, $arguments) {
        $presenter = new stdClass;
        $this->render('Campaign/'. $name, $presenter);
    }

    public function index() {
        $presenter = new Testing_Campaign_CampaignCollectionPresenter(
            $this->presenter_collection_factory->getListOfCampaignPresenters($this->request->getProject())
        );
        $this->render('Campaign/index', $presenter);
    }

    public function create() {
        $GLOBALS['Response']->addFeedback('info', 'The milestone has been successfuly created');
        $GLOBALS['Response']->redirect('/plugins/testing/?group_id=1');
    }
}
