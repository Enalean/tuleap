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

export default MainController;

MainController.$inject = ["$element", "SharedPropertiesService", "amMoment", "gettextCatalog"];

function MainController($element, SharedPropertiesService, amMoment, gettextCatalog) {
    init();

    function init() {
        const planning_init_data = $element[0].querySelector(".planning-init-data").dataset;

        const user_id = planning_init_data.userId;
        SharedPropertiesService.setUserId(user_id);
        const project_id = planning_init_data.projectId;
        SharedPropertiesService.setProjectId(project_id);
        const milestone_id = planning_init_data.milestoneId;
        SharedPropertiesService.setMilestoneId(milestone_id);
        const view_mode = planning_init_data.viewMode;
        SharedPropertiesService.setViewMode(view_mode);
        const is_in_explicit_top_backlog = planning_init_data.isInExplicitTopBacklog;
        SharedPropertiesService.setIsInExplicitTopBacklogManagement(is_in_explicit_top_backlog);
        setAccessibilityMode(JSON.parse(planning_init_data.userAccessibilityMode));
        SharedPropertiesService.setAllowedAdditionalPanesToDisplay(
            JSON.parse(planning_init_data.allowedAdditionalPanesToDisplay)
        );
        SharedPropertiesService.setCreateMilestoneAllowed(
            JSON.parse(planning_init_data.createMilestoneAllowed)
        );
        SharedPropertiesService.setAddItemInBacklogAllowed(
            JSON.parse(planning_init_data.backlogAddItemAllowed)
        );

        const is_list_picker_enabled = Boolean(JSON.parse(planning_init_data.isListPickerEnabled));
        SharedPropertiesService.setEnableListPicker(is_list_picker_enabled);

        const language = planning_init_data.language;
        initLocale(language);
    }

    function initLocale(lang) {
        gettextCatalog.setCurrentLanguage(lang);
        amMoment.changeLocale(lang);
        moment.locale(lang);
    }
}
