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

use Feedback;
use Project;
use Tracker;
use Tracker_Exception;
use TrackerFactory;
use TrackerFromXmlException;
use TrackerXmlImport;
use Tuleap\Tracker\TrackerIsInvalidException;
use UserManager;
use XML_ParseException;
use XMLImportHelper;

class TrackerCreator
{
    /**
     * @var TrackerXmlImport
     */
    private $tracker_xml_import;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var TrackerCreatorXmlErrorDisplayer
     */
    private $xml_error_displayer;
    /**
     * @var TrackerCreationDataChecker
     */
    private $creation_data_checker;

    public function __construct(
        TrackerXmlImport $tracker_xml_import,
        TrackerFactory $tracker_factory,
        TrackerCreatorXmlErrorDisplayer $xml_error_displayer,
        TrackerCreationDataChecker $creation_data_checker
    ) {
        $this->tracker_xml_import    = $tracker_xml_import;
        $this->tracker_factory       = $tracker_factory;
        $this->xml_error_displayer   = $xml_error_displayer;
        $this->creation_data_checker = $creation_data_checker;
    }

    public static function build(): self
    {
        $user_finder        = new XMLImportHelper(UserManager::instance());
        $tracker_xml_import = TrackerXmlImport::build($user_finder);

        return new TrackerCreator(
            $tracker_xml_import,
            TrackerFactory::instance(),
            TrackerCreatorXmlErrorDisplayer::build(),
            new TrackerCreationDataChecker(
                \ReferenceManager::instance(),
                new \TrackerDao(),
                TrackerFactory::instance()
            )
        );
    }

    /**
     * @throws Tracker_Exception
     * @throws TrackerIsInvalidException
     * @throws TrackerCreationHasFailedException
     */
    public function createTrackerFromXml(
        Project $project,
        string $file_path,
        string $name,
        string $description,
        string $itemname,
        ?string $color
    ): Tracker {
        try {
            return $this->tracker_xml_import->createFromXMLFileWithInfo(
                $project,
                $file_path,
                $name,
                $description,
                $itemname,
                $color
            );
        } catch (XML_ParseException $exception) {
            $this->xml_error_displayer->displayErrors($project, $exception->getErrors(), $exception->getFileLines());
            throw new TrackerCreationHasFailedException();
        } catch (TrackerFromXmlException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            throw new TrackerCreationHasFailedException();
        }
    }

    /**
     * @throws TrackerCreationHasFailedException
     * @throws \Tuleap\Tracker\TrackerIsInvalidException
     */
    public function duplicateTracker(
        Project $project,
        string $name,
        string $description,
        string $itemname,
        ?string $color,
        string $atid_template,
        \PFUser $user
    ): Tracker {
        $this->creation_data_checker->checkAtTrackerDuplication($itemname, $atid_template, $user);
        $duplicate = $this->tracker_factory->create(
            $project->getId(),
            -1,
            $atid_template,
            $name,
            $description,
            $itemname,
            $color
        );

        if (! $duplicate || ! $duplicate['tracker']) {
            throw new TrackerCreationHasFailedException();
        }

        return $duplicate['tracker'];
    }
}
