<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Admin\GlobalAdmin\Trackers;

use EventManager;
use Feedback;
use ForgeConfig;
use HTTPRequest;
use ReferenceManager;
use Tracker;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\FormElement\Field\FieldDao;

class MarkTrackerAsDeletedController implements DispatchableWithRequest
{
    public const DELETION_URL = "delete-tracker";
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var CSRFSynchronizerTokenProvider
     */
    private $token_provider;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ReferenceManager
     */
    private $reference_manager;
    /**
     * @var GlobalAdminPermissionsChecker
     */
    private $permissions_checker;

    /**
     * @var FieldDao
     */
    private $field_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        GlobalAdminPermissionsChecker $permissions_checker,
        CSRFSynchronizerTokenProvider $token_provider,
        EventManager $event_manager,
        ReferenceManager $reference_manager,
        FieldDao $field_dao,
    ) {
        $this->tracker_factory     = $tracker_factory;
        $this->token_provider      = $token_provider;
        $this->event_manager       = $event_manager;
        $this->reference_manager   = $reference_manager;
        $this->permissions_checker = $permissions_checker;
        $this->field_dao           = $field_dao;
    }

    public static function getURL(Tracker $tracker): string
    {
        return TRACKER_BASE_URL . '/' . self::DELETION_URL . '/' . urlencode((string) $tracker->getId());
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $tracker = $this->getTracker($variables);
        if (
            ! $this->permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject(
                $tracker->getProject(),
                $request->getCurrentUser()
            )
        ) {
            throw new ForbiddenException();
        }

        $project = $tracker->getProject();
        $this->token_provider->getCSRF($project)->check();

        $service_usage_for_tracker = $tracker->getInformationsFromOtherServicesAboutUsage();
        if ($service_usage_for_tracker['can_be_deleted'] === false) {
            throw new ForbiddenException(
                sprintf(
                    dgettext('tuleap-tracker', 'You can\'t delete this tracker because it is used in: %1$s'),
                    $service_usage_for_tracker['message']
                )
            );
        } elseif ($this->field_dao->doesTrackerHaveSourceSharedFields($tracker->getId()) === true) {
            throw new ForbiddenException(
                dgettext('tuleap-tracker', 'You can\'t delete this tracker because it has at least one source shared field.')
            );
        }

        $this->markTrackerAsDeleted($tracker, $layout);

        $layout->redirect(TrackersDisplayController::getURL($project));
    }

    private function getTracker(array $variables): Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($variables['id']);
        if (! $tracker) {
            throw new NotFoundException();
        }

        if ($tracker->isDeleted()) {
            throw new NotFoundException();
        }

        return $tracker;
    }

    private function markTrackerAsDeleted(Tracker $tracker, BaseLayout $layout): void
    {
        if (! $this->tracker_factory->markAsDeleted($tracker->getId())) {
            $layout->addFeedback(
                Feedback::ERROR,
                sprintf(
                    dgettext('tuleap-tracker', 'An error occurred while trying to delete the tracker %s'),
                    $tracker->getName()
                )
            );

            return;
        }

        $this->event_manager->processEvent(TRACKER_EVENT_DELETE_TRACKER, ['tracker_id' => $tracker->getId()]);

        $layout->addFeedback(
            Feedback::INFO,
            sprintf(
                dgettext('tuleap-tracker', 'Tracker %s has been successfully deleted'),
                $tracker->getName()
            )
        );
        $layout->addFeedback(
            Feedback::INFO,
            sprintf(
                dgettext(
                    'tuleap-tracker',
                    'In case you have inadvertently deleted this tracker and want it to be restored, please contact the <a href="mailto:%1$s">Site Administrator</a> within the next 10 days.'
                ),
                ForgeConfig::get('sys_email_admin')
            ),
            CODENDI_PURIFIER_LIGHT
        );

        $this->deleteTrackerReference($tracker, $layout);
    }

    private function deleteTrackerReference(Tracker $tracker, BaseLayout $layout): void
    {
        $ref = $this->reference_manager->loadReferenceFromKeywordAndNumArgs(
            strtolower($tracker->getItemName()),
            $tracker->getProject()->getID(),
            1
        );
        if (! $ref) {
            return;
        }

        if ($this->reference_manager->deleteReference($ref)) {
            $layout->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('project_reference', 't_r_deleted')
            );
        }
    }
}
