/*
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

export default ReleaseController;

ReleaseController.$inject = ["gettextCatalog", "ReleaseRestService", "SharedPropertiesService"];

function ReleaseController(gettextCatalog, ReleaseRestService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        error_no_release_artifact: false,
        milestone: null,
        translated_package_name: "",
        $onInit: init,
    });

    function init() {
        Object.assign(self, {
            project_id: SharedPropertiesService.getProjectId(),
            release: SharedPropertiesService.getRelease(),
        });
        self.translated_package_name = gettextCatalog.getString("Package {{ package_name }}", {
            package_name: self.release.package.label,
        });

        if (!doesReleaseArtifactExist()) {
            self.error_no_release_artifact = true;
            return;
        }

        ReleaseRestService.getMilestone(self.release.artifact.id).then(function (milestone) {
            self.milestone = milestone;
        });
    }

    function doesReleaseArtifactExist() {
        return self.release.artifact !== null && self.release.artifact.id !== null;
    }
}
