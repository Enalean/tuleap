<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElement_Chart_Field_Exception;
use Tracker_FormElement_Field;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

class ChartConfigurationFieldRetriever
{
    public const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';
    public const CAPACITY_FIELD_NAME         = 'capacity';

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_field_factory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;

    public function __construct(
        Tracker_FormElementFactory $form_element_field_factory,
        SemanticTimeframeBuilder $semantic_timeframe_builder,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->form_element_field_factory = $form_element_field_factory;
        $this->semantic_timeframe_builder = $semantic_timeframe_builder;
        $this->logger                     = $logger;
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getCapacityField(Tracker $tracker)
    {
        $field = $this->form_element_field_factory->getNumericFieldByName(
            $tracker,
            self::CAPACITY_FIELD_NAME
        );

        if (! $field) {
            $this->logger->info("Tracker " . $tracker->getName() . " doesn't have a capacity field (or field is not properly set)");
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "capacity" Integer or Float or Computed field or you don\'t have the permission to access it.')
            );
        }

        return $field;
    }

    /**
     * @return Tracker_FormElement_Field
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getDurationField(Tracker $tracker, PFUser $user)
    {
        $semantic = $this->semantic_timeframe_builder->getSemantic($tracker);

        $field = $semantic->getDurationField();

        if (! $field || ! $field->userCanRead($user)) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "duration" Integer field or you don\'t have the permission to access it.')
            );
        }

        return $field;
    }

    /**
     * @return Tracker_FormElement_Field
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getEndDateField(Tracker $tracker, PFUser $user)
    {
        $semantic = $this->semantic_timeframe_builder->getSemantic($tracker);

        $field = $semantic->getEndDateField();

        if (! $field || ! $field->userCanRead($user)) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "end_date" Date field or you don\'t have the permission to access it.')
            );
        }

        return $field;
    }

    /**
     * @return Tracker_FormElement_Field
     * @throws Tracker_FormElement_Chart_Field_Exception
     */
    public function getStartDateField(Tracker $tracker, PFUser $user)
    {
        $semantic = $this->semantic_timeframe_builder->getSemantic($tracker);

        $field = $semantic->getStartDateField();

        if (! $field || ! $field->userCanRead($user)) {
            throw new Tracker_FormElement_Chart_Field_Exception(
                dgettext('tuleap-tracker', 'The tracker doesn\'t have a "start_date" Date field or you don\'t have the permission to access it.')
            );
        }

        return $field;
    }

    /**
     * @return bool|Tracker_FormElement_Field
     */
    public function getBurndownRemainingEffortField(Tracker_Artifact $artifact, PFUser $user)
    {
        return $this->form_element_field_factory->getNumericFieldByNameForUser(
            $artifact->getTracker(),
            $user,
            self::REMAINING_EFFORT_FIELD_NAME
        );
    }

    /**
     * @return bool
     */
    public function doesRemainingEffortFieldExists(Tracker $tracker)
    {
        return ! $this->form_element_field_factory->getNumericFieldByName(
            $tracker,
            self::REMAINING_EFFORT_FIELD_NAME
        );
    }

    /**
     * @return bool
     */
    public function doesCapacityFieldExist(Tracker $tracker)
    {
        try {
            $this->getCapacityField($tracker);

            return true;
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            return false;
        }
    }

    public function doesEndDateFieldExist(Tracker $tracker, PFUser $user): bool
    {
        try {
            $this->getEndDateField($tracker, $user);

            return true;
        } catch (Tracker_FormElement_Chart_Field_Exception $e) {
            return false;
        }
    }
}
