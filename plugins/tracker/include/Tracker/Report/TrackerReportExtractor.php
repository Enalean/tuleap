<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

namespace Tuleap\Tracker\Report;

class TrackerReportExtractor
{
    /**
     * @var \TrackerFactory
     */
    private $tracker_factory;

    public function __construct(\TrackerFactory $tracker_factory)
    {
        $this->tracker_factory = $tracker_factory;
    }

    /**
     * @param array $trackers_id
     *
     * @return array
     * @throws TrackerDuplicateException
     * @throws TrackerNotFoundException
     */
    public function extractTrackers(array $trackers_id)
    {
        $invalid_tracker = array();
        $list            = array();
        foreach ($trackers_id as $tracker_id) {
            $tracker = $this->tracker_factory->getTrackerById($tracker_id);
            if ($tracker && $tracker->userCanView() && ! $tracker->isDeleted() && $tracker->getProject()->isActive()) {
                $list[] = $tracker;
            } elseif (! $tracker) {
                $invalid_tracker[] = $tracker_id;
            }
        }

        if (count($invalid_tracker) > 0) {
            throw new TrackerNotFoundException(
                sprintf(
                    dgettext('tuleap-tracker', 'One tracker or more are not found: %s'),
                    implode(',', $invalid_tracker)
                )
            );
        }

        $duplicates = array_diff_key($list, array_unique($list));
        if (count($duplicates) > 0) {
            throw new TrackerDuplicateException(
                sprintf(
                    dgettext('tuleap-tracker', 'One tracker or more is duplicated in list: %s'),
                    implode(',', $duplicates)
                )
            );
        }

        return $list;
    }
}
