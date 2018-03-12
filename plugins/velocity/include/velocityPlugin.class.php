<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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


use Tuleap\AgileDashboard\Planning\AdditionalPlanningConfigurationWarningsRetriever;
use Tuleap\AgileDashboard\Semantic\Dao\SemanticDoneDao;
use Tuleap\AgileDashboard\Semantic\SemanticDone;
use Tuleap\Velocity\Semantic\SemanticVelocity;

require_once 'autoload.php';
require_once 'constants.php';

class velocityPlugin extends Plugin // @codingStandardsIgnoreLine
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindTextDomain('tuleap-velocity', VELOCITY_BASE_DIR . '/site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(TRACKER_EVENT_MANAGE_SEMANTICS);
        $this->addHook(AdditionalPlanningConfigurationWarningsRetriever::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getDependencies()
    {
        return array('tracker', 'agiledashboard');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\Velocity\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    /**
     * @see Event::TRACKER_EVENT_MANAGE_SEMANTICS
     */
    public function tracker_event_manage_semantics($parameters) // @codingStandardsIgnoreLine
    {
        $tracker = $parameters['tracker'];
        /* @var $semantics Tracker_SemanticCollection */
        $semantics = $parameters['semantics'];

        if (! $this->isAPlanningTrackers($tracker)) {
            return;
        }

        $semantics->insertAfter(SemanticDone::NAME, SemanticVelocity::load($tracker));
    }


    private function isAPlanningTrackers(Tracker $semantic_tracker)
    {
        $planning_factory = PlanningFactory::build();
        $planning         = $planning_factory->getPlanningByPlanningTracker($semantic_tracker);

        if ($planning) {
            return $semantic_tracker->getId() === $planning->getPlanningTrackerId();
        }

        return false;
    }

    public function additionalPlanningConfigurationWarningsRetriever(
        AdditionalPlanningConfigurationWarningsRetriever $event
    ) {
        $velocity = SemanticVelocity::load($event->getTracker());

        if ($velocity->getVelocityField()) {
            $event->addWarnings(
                sprintf(
                    dgettext(
                        'tuleap-velocity',
                        'The tracker %s uses a Velocity semantic. Please check its configuration.'
                    ),
                    $event->getTracker()->getName()
                )
            );
        }
    }
}
