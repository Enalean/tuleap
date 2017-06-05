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

namespace Tuleap\Tracker\Report;

class WidgetAdditionalButtonPresenter
{
    public $new_artifact;
    public $url_artifact_submit;
    public $is_a_table_renderer;

    public function __construct(\Tracker $tracker, \HTTPRequest $request, $is_a_table_renderer)
    {
        $this->new_artifact        = sprintf(
            dgettext('tuleap-tracker', 'Add a new %s'),
            $tracker->getItemName()
        );
        $this->url_artifact_submit = $request->getServerUrl() .
            "/plugins/tracker/?tracker=" . urlencode($tracker->getId()) . "&func=new-artifact";

        $this->is_a_table_renderer = $is_a_table_renderer;
    }
}
