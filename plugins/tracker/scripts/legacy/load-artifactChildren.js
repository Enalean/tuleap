/**
 * Copyright (c) Enalean, 2013 - 2016. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/* global jQuery:readonly tuleap:readonly codendi:readonly */

(function ($) {
    $(document).ready(function () {
        var hierarchy_containers = $(".artifact-hierarchy");
        var nature_containers = $(".artifact-nature");

        var containers = hierarchy_containers.add(nature_containers);

        containers.each(function () {
            var container = $(this),
                artifact_id = container.data("artifactId"),
                //eslint-disable-next-line @typescript-eslint/no-unused-vars
                hierarchy_viewer = new tuleap.artifact.HierarchyViewer(
                    codendi.tracker.base_url,
                    container.get(0),
                    codendi.locales,
                    codendi.imgroot,
                    artifact_id
                );
        });
    });
})(jQuery);
