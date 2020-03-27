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

namespace Tuleap\TestManagement\Campaign;

class Campaign
{
    /**
     * @var \Tracker_Artifact
     */
    private $artifact;
    /**
     * @var string
     */
    private $label;
    /**
     * @var JobConfiguration
     */
    private $job_configuration;

    /**
     *
     *
     * @param string            $label
     */
    public function __construct(\Tracker_Artifact $artifact, $label, JobConfiguration $job_configuration)
    {
        $this->artifact          = $artifact;
        $this->label             = $label;
        $this->job_configuration = $job_configuration;
    }

    /**
     * @return \Tracker_Artifact
     */
    public function getArtifact()
    {
        return $this->artifact;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return JobConfiguration
     */
    public function getJobConfiguration()
    {
        return $this->job_configuration;
    }

    /**
     * @param string $label
     *
     */
    public function setLabel($label): void
    {
        $this->label = $label;
    }

    public function setJobConfiguration(JobConfiguration $job_configuration): void
    {
        $this->job_configuration = $job_configuration;
    }
}
