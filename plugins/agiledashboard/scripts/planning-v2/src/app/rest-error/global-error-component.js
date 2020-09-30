/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import { reload } from "./location-helper";

const template = `<div class="empty-page">
    <div class="empty-page-illustration"></div>

    <div class="empty-page-text-with-small-text">
        {{::"There's been an issue" | translate }}
        <div class="empty-page-small-text">
            {{::"It seems an action you tried to perform can't be done" | translate }}
        </div>
        <div class="planning-error-link">
            <a ng-if="!$ctrl.is_more_shown" ng-click="$ctrl.is_more_shown = true" translate>Show error details</a>
        </div>
        <pre ng-if="$ctrl.is_more_shown" class="planning-error-details">{{ $ctrl.getErrorMessage() }}</pre>
    </div>

    <button type="button" class="tlp-button-primary tlp-button-large" ng-click="$ctrl.reloadPage()">
        <i class="fas fa-sync tlp-button-icon"></i>
        <span translate>Reload the page</span>
    </button>
</div>`;

controller.$inject = ["ErrorState"];

function controller(ErrorState) {
    const self = this;
    Object.assign(self, {
        is_more_shown: false,
        getErrorMessage: () => ErrorState.getError(),
        reloadPage: () => reload(),
    });
}

export default {
    template,
    controller,
};
