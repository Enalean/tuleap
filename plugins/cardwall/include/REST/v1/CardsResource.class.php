<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
namespace Tuleap\Cardwall\REST\v1;

use CardControllerBuilderRequestDataException;
use CardControllerBuilderRequestIdException;
use CardControllerBuilderRequestPlanningIdException;
use Cardwall_CardFields;
use Cardwall_OnTop_ConfigFactory;
use Cardwall_SingleCardBuilder;
use Luracast\Restler\RestException;
use PFUser;
use PlanningFactory;
use Tracker_ArtifactFactory;
use Tracker_Exception;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\REST\Header;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorColorRetriever;
use URLVerification;
use UserManager;

class CardsResource {

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var Cardwall_OnTop_ConfigFactory */
    private $config_factory;

    /** @var Cardwall_SingleCardBuilder */
    private $single_card_builder;

    public function __construct()
    {
        $this->user_manager        = UserManager::instance();
        $this->formelement_factory = Tracker_FormElementFactory::instance();
        $this->config_factory      = new Cardwall_OnTop_ConfigFactory(
            TrackerFactory::instance(),
            $this->formelement_factory
        );

        $this->single_card_builder = new Cardwall_SingleCardBuilder(
            $this->config_factory,
            new Cardwall_CardFields(
                $this->user_manager,
                $this->formelement_factory
            ),
            Tracker_ArtifactFactory::instance(),
            PlanningFactory::build(),
            new BackgroundColorBuilder(
                new BindDecoratorColorRetriever()
            )
        );
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptions();
    }

    /**
     * Return info card if exists
     *
     * @url OPTIONS {id}
     *
     * @param string $id Id of the card
     *
     * @throws 403
     * @throws 404
     */
    public function optionsId($id) {
        Header::allowOptionsPut();
    }

    /**
     * Update card content
     *
     * Things to take into account:
     * <ol>
     *  <li>You will get an error (400) if there are no changes in submitted document</li>
     *  <li>You can re-use the same document provided by /milestones/:id/cardwall cards
     *      section. Even if it contains more data. The extra data/info will be ignored</li>
     *  <li>You don't need to set all 'values' of the card, you can restrict to the modified ones</li>
     *  <li>If the card embbed in 'values' a field that correspond to 'label' or 'column_id'
     *      (ie. that might happens if the status is displayed on the card). The data submitted
     *      in 'values' section will override whatever was set in 'label' or 'column_id'</li>
     * </ol>
     *
     * @url PUT {id}
     * @param string $id        Id of the card (format: planningId_artifactId, @see milestones/:id/cardwall)
     * @param string $label     Label of the card {@from body}
     * @param array  $values    Card's fields values {@from body}
     * @param int    $column_id Where the card should stands {@from body}
     *
     */
    protected function putId($id, $label, array $values, $column_id = null) {
        try {
            $current_user = $this->user_manager->getCurrentUser();
            $single_card  = $this->getSingleCard($current_user, $id);

            $card_updater = new CardUpdater();
            $card_updater->updateCard($current_user, $single_card, $label, $values, $column_id);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(400, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(400, $exception->getMessage());
        }

        Header::allowOptionsPut();
    }

    private function getSingleCard(PFUser $user, $id) {
        try {
            $this->checkIdIsWellFormed($id);
            list($planning_id, $artifact_id) = explode('_', $id);
            $single_card = $this->single_card_builder->getSingleCard($user, $artifact_id, $planning_id);
            if ($single_card->getArtifact()->userCanView($user)) {
                ProjectAuthorization::userCanAccessProject(
                    $user,
                    $single_card->getArtifact()->getTracker()->getProject(),
                     new URLVerification()
                );
                return $single_card;
            }
            throw new RestException(403);
        } catch (CardControllerBuilderRequestIdException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (CardControllerBuilderRequestDataException $exception) {
            throw new RestException(404, $exception->getMessage());
        } catch (CardControllerBuilderRequestPlanningIdException $exception) {
            throw new RestException(404, $exception->getMessage());
        }
        throw new RestException(404);
    }

    /**
     * Checks if the given id is well formed (format: planningid_artifactid)
     *
     * @param string $id Id of the card (format: planningId_artifactId)
     *
     * @return boolean
     *
     * @throws 400
     */
    private function checkIdIsWellFormed($id) {
        $regexp = '/^[0-9]+_[0-9]+$/';

        if (! preg_match($regexp, $id)) {
            throw new RestException(400, 'Invalid id format, format must be: planningid_artifactid');
        }

        return true;
    }
}
