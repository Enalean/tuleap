<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Tuleap\Tracker\Milestone\PaneInfo;

class AgileDashboardPaneInfo extends PaneInfo
{
    private $release_id;

    public function __construct($release_id)
    {
        parent::__construct();
        $this->release_id = $release_id;
    }

    /** @see PaneInfo::getIdentifier */
    #[\Override]
    public function getIdentifier()
    {
        return 'frs';
    }

    #[\Override]
    public function isExternalLink()
    {
        return true;
    }

    /** @see PaneInfo::getTitle */
    #[\Override]
    public function getTitle()
    {
        return dgettext('tuleap-frs', 'File release');
    }

    #[\Override]
    public function getUri(): string
    {
        $release_id = urlencode((string) $this->release_id);
        return "/frs/release/$release_id/release-notes";
    }

    #[\Override]
    public function getIconName(): string
    {
        return 'fa-regular fa-copy';
    }
}
