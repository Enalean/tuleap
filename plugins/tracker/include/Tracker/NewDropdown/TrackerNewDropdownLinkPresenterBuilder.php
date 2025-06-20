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

namespace Tuleap\Tracker\NewDropdown;

use Tuleap\Layout\NewDropdown\DataAttributePresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter;

final class TrackerNewDropdownLinkPresenterBuilder
{
    public function build(\Tuleap\Tracker\Tracker $tracker): NewDropdownLinkPresenter
    {
        return $this->buildWithAdditionalDataAttributes($tracker, []);
    }

    /**
     * @param array<string, string|array> $url_parameters
     */
    public function buildWithAdditionalUrlParameters(\Tuleap\Tracker\Tracker $tracker, array $url_parameters): NewDropdownLinkPresenter
    {
        return $this->buildTrackerLink($tracker, $tracker->getSubmitUrlWithParameters($url_parameters), []);
    }

    /**
     * @param DataAttributePresenter[] $data_attributes
     */
    public function buildWithAdditionalDataAttributes(\Tuleap\Tracker\Tracker $tracker, array $data_attributes): NewDropdownLinkPresenter
    {
        return $this->buildTrackerLink($tracker, $tracker->getSubmitUrl(), $data_attributes);
    }

    /**
     * @param DataAttributePresenter[] $data_attributes
     */
    private function buildTrackerLink(\Tuleap\Tracker\Tracker $tracker, string $url, array $data_attributes): NewDropdownLinkPresenter
    {
        return new NewDropdownLinkPresenter(
            $url,
            sprintf(
                dgettext('tuleap-tracker', 'New %s'),
                $tracker->getItemName()
            ),
            'fa-plus',
            array_merge(
                [new DataAttributePresenter('tracker-id', (string) $tracker->getId())],
                $data_attributes
            ),
        );
    }
}
