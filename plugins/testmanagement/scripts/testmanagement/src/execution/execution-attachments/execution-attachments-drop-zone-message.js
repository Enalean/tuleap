/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { sprintf } from "sprintf-js";
import prettyKibibytes from "pretty-kibibytes";
import "./execution-attachments-drop-zone-message.tpl.html";

export default {
    templateUrl: "execution-attachments-drop-zone-message.tpl.html",
    controller,
};

controller.$inject = ["$rootScope", "$scope", "gettextCatalog", "SharedPropertiesService"];

function controller($rootScope, $scope, gettextCatalog, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        $onInit,
        getMessage,
        is_shown: false,
    });

    function $onInit() {
        $rootScope.$on("drop-zone-active", show);
        $rootScope.$on("drop-zone-inactive", hide);
    }

    function show() {
        self.is_shown = true;
        $scope.$apply();
    }

    function hide() {
        self.is_shown = false;
        $scope.$apply();
    }

    function getMessage() {
        return sprintf(
            gettextCatalog.getString(
                "Drop files here to attach them to your comment (max size is %ss).",
            ),
            prettyKibibytes(SharedPropertiesService.getFileUploadMaxSize()),
        );
    }
}
