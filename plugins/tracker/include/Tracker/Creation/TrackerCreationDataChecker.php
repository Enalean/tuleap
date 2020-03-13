<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use Tracker;
use TrackerDao;
use TrackerFactory;
use Tuleap\Tracker\TrackerIsInvalidException;

class TrackerCreationDataChecker
{
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;
    /**
     * @var TrackerDao
     */
    private $tracker_dao;
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function __construct(
        \ReferenceManager $reference_manager,
        TrackerDao $tracker_dao,
        \TrackerFactory $tracker_factory
    ) {
        $this->reference_manager = $reference_manager;
        $this->tracker_dao       = $tracker_dao;
        $this->tracker_factory   = $tracker_factory;
    }

    public static function build(): self
    {
        return new TrackerCreationDataChecker(
            \ReferenceManager::instance(),
            new TrackerDao(),
            TrackerFactory::instance()
        );
    }

    public function areMandatoryCreationInformationValid(
        $tracker_name,
        $tracker_shortname,
        int $project_id
    ): bool {
        return $this->isRequiredInformationAvailable($tracker_name, $tracker_shortname)
            && $this->isShortNameValid($tracker_shortname) && ! $this->doesNameExistsForProject(
                $tracker_name,
                $project_id
            )
            && ! $this->doesShortNameExists($tracker_shortname, $project_id)
            && ! $this->reference_manager->_isKeywordExists($tracker_shortname, $project_id);
    }

    /**
     * Used in template inheritance and XML import context
     * @throws TrackerIsInvalidException
     */
    public function checkAtProjectCreation(int $project_id, $public_name, $shortname): void
    {
        if (! $this->isRequiredInformationAvailable($public_name, $shortname)) {
            throw TrackerIsInvalidException::buildMissingRequiredProperties();
        }

        // Necessary test to avoid issues when exporting the tracker to a DB (e.g. '-' not supported as table name)
        if (! $this->isShortNameValid($shortname)) {
            throw TrackerIsInvalidException::shortnameIsInvalid($shortname);
        }

        if ($this->doesNameExistsForProject($public_name, $project_id)) {
            throw TrackerIsInvalidException::nameAlreadyExists($public_name);
        }

        if ($this->doesShortNameExists($shortname, $project_id)) {
            throw TrackerIsInvalidException::shortnameAlreadyExists($shortname);
        }

        if ($this->reference_manager->_isKeywordExists($shortname, $project_id)) {
            throw TrackerIsInvalidException::shortnameAlreadyExists($shortname);
        }
    }

    /**
     * @throws TrackerIsInvalidException
     */
    public function checkAndRetrieveTrackerTemplate(int $id_template): Tracker
    {
        // Get the template tracker
        $template_tracker =  $this->tracker_factory->getTrackerById($id_template);
        if (! $template_tracker) {
            throw TrackerIsInvalidException::invalidTrackerTemplate();
        }

        $template_group = $template_tracker->getProject();
        if (! $template_group || ! is_object($template_group) || $template_group->isError()) {
            throw TrackerIsInvalidException::invalidProjectTemplate();
        }

        return $template_tracker;
    }

    private function isShortNameValid(string $shortname): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/i', $shortname) === 1;
    }

    private function isRequiredInformationAvailable($name, $itemname)
    {
        return trim($name) !== '' && trim($itemname) !== '';
    }

    public function doesShortNameExists(string $shortname, int $project_id): bool
    {
        return $this->tracker_dao->isShortNameExists($shortname, $project_id);
    }

    private function doesNameExistsForProject(string $name, int $project_id): bool
    {
        return $this->tracker_dao->doesTrackerNameAlreadyExist($name, $project_id);
    }

    /**
     * Used in tracker creation context
     * @throws TrackerIsInvalidException
     */
    public function checkAtTrackerDuplication(string $shortname, string $tracker_template_id, \PFUser $user): void
    {
        if (strlen($shortname) > Tracker::MAX_TRACKER_SHORTNAME_LENGTH) {
            throw TrackerIsInvalidException::buildInvalidLength();
        }

        $tracker = $this->tracker_factory->getTrackerById((int) $tracker_template_id);
        if (! $tracker) {
            throw TrackerIsInvalidException::trackerNotFound($tracker_template_id);
        }

        if ($tracker->getProject()->isTemplate()) {
            return;
        }

        if (! $tracker->userIsAdmin($user)) {
            throw TrackerIsInvalidException::trackerNotFound($tracker_template_id);
        }
    }
}
