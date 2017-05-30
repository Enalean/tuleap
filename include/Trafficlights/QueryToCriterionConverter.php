<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use Tracker_ArtifactFactory;
use stdClass;
use Tuleap\Trafficlights\Criterion\StatusAll;
use Tuleap\Trafficlights\Criterion\StatusClosed;
use Tuleap\Trafficlights\Criterion\StatusOpen;
use Tuleap\Trafficlights\Criterion\MilestoneAll;
use Tuleap\Trafficlights\Criterion\MilestoneFilter;


class QueryToCriterionConverter {

    /** @var ConfigConformanceValidator */
    private $config_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(
        ConfigConformanceValidator $config_validator,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->config_validator = $config_validator;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @param string $query
     * @return StatusAll|StatusClosed|StatusOpen
     * @throws MalformedQueryParameterException
     */
    public function convertStatus($query) {
        $error_message = 'Expecting {"status":"open"} or {"status":"closed"}.';

        if (! isset($query)) {
            return new StatusAll();
        }

        $query_object = json_decode(stripslashes($query));

        if (! is_object($query_object)) {
            throw new MalformedQueryParameterException();
        }

        if (! isset($query_object->status)) {
            return new StatusAll();
        }

        if ($query_object->status === 'open') {
            return new StatusOpen();
        } else if ($query_object->status === 'closed') {
            return new StatusClosed();
        } else {
            throw new MalformedQueryParameterException($error_message);
        }
    }

    /**
     * @param string $query
     * @return MilestoneAll|MilestoneFilter
     * @throws MalformedQueryParameterException
     */
    public function convertMilestone($query) {
        $error_message = 'Expecting {"milestone_id":<id>}.';

        if (! isset($query)) {
            return new MilestoneAll();
        }

        $query_object = json_decode(stripslashes($query));

        if (! is_object($query_object)) {
            throw new MalformedQueryParameterException();
        }

        if (! isset($query_object->milestone_id)) {
            return new MilestoneAll();
        }

        if ($query_object->milestone_id === 0) {
            return new MilestoneAll();
        } else if (is_int($query_object->milestone_id)) {
            return new MilestoneFilter($query_object->milestone_id);
        } else {
            throw new MalformedQueryParameterException($error_message);
        }
    }
}
