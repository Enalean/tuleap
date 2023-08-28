/*
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

import moment from "moment";
import { setAccessibilityMode } from "./user-accessibility-mode.js";
import { setSuccess } from "./success-state.js";
import { SESSION_STORAGE_KEY } from "./session";

export default MainController;

MainController.$inject = [
    "$element",
    "$window",
    "SharedPropertiesService",
    "amMoment",
    "gettextCatalog",
];

function MainController($element, $window, SharedPropertiesService, amMoment, gettextCatalog) {
    init();

    function init() {
        if (!$element[0].querySelector(".planning-init-data")) {
            return;
        }
        const planning_init_data = $element[0].querySelector(".planning-init-data").dataset;

        const user_id = planning_init_data.userId;
        SharedPropertiesService.setUserId(user_id);
        const project_id = planning_init_data.projectId;
        SharedPropertiesService.setProjectId(project_id);
        const milestone_id = planning_init_data.milestoneId;
        SharedPropertiesService.setMilestoneId(milestone_id);
        const view_mode = planning_init_data.viewMode;
        SharedPropertiesService.setViewMode(view_mode);
        const is_in_explicit_top_backlog = planning_init_data.isInExplicitTopBacklog === "1";
        SharedPropertiesService.setIsInExplicitTopBacklogManagement(is_in_explicit_top_backlog);
        setAccessibilityMode(planning_init_data.userAccessibilityMode === "1");
        SharedPropertiesService.setAllowedAdditionalPanesToDisplay(
            JSON.parse(planning_init_data.allowedAdditionalPanesToDisplay)
        );
        const is_split_feature_flag_enabled = planning_init_data.isSplitFeatureFlagEnabled === "1";
        SharedPropertiesService.setIsSplitFeatureFlagEnabled(is_split_feature_flag_enabled);

        const language = planning_init_data.language;
        initLocale(language);

        const success_feedback = $window.sessionStorage.getItem(SESSION_STORAGE_KEY);
        if (success_feedback !== null) {
            setSuccess(success_feedback);
            $window.sessionStorage.removeItem(SESSION_STORAGE_KEY);
        }
    }

    function initLocale(lang) {
        gettextCatalog.setCurrentLanguage(lang);
        amMoment.changeLocale(lang);
        moment.locale(lang);
    }
}
