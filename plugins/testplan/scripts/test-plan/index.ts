/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
 *
 */

import Vue from "vue";
import Vuex from "vuex";
import {
    getPOFileFromLocale,
    initVueGettext,
} from "../../../../src/scripts/tuleap/gettext/vue-gettext-init";
import App from "./src/components/App.vue";
import { createStore } from "./src/store";
import { RootState } from "./src/store/type";

document.addEventListener("DOMContentLoaded", async () => {
    const vue_mount_point = document.getElementById("test-plan");
    if (!vue_mount_point) {
        return;
    }

    const user_display_name = vue_mount_point.dataset.userDisplayName || "";
    const project_id = Number.parseInt(vue_mount_point.dataset.projectId || "0", 10);
    const project_name = vue_mount_point.dataset.projectName || "";
    const milestone_id = Number.parseInt(vue_mount_point.dataset.milestoneId || "0", 10);
    const expand_backlog_item_id = Number.parseInt(
        vue_mount_point.dataset.expandBacklogItemId || "0",
        10
    );
    let highlight_test_definition_id: number | null = Number.parseInt(
        vue_mount_point.dataset.highlightTestDefinitionId || "0",
        10
    );
    if (highlight_test_definition_id === 0) {
        highlight_test_definition_id = null;
    }
    const milestone_title = vue_mount_point.dataset.milestoneTitle || "";
    const user_can_create_campaign = vue_mount_point.dataset.userCanCreateCampaign === "1";
    let testdefinition_tracker_id: number | null = Number.parseInt(
        vue_mount_point.dataset.testDefinitionTrackerId || "0",
        10
    );
    if (testdefinition_tracker_id === 0) {
        testdefinition_tracker_id = null;
    }
    const testdefinition_tracker_name = vue_mount_point.dataset.testDefinitionTrackerName || "";

    await initVueGettext(
        Vue,
        (locale: string) =>
            import(/* webpackChunkName: "testplan-po-" */ "./po/" + getPOFileFromLocale(locale))
    );
    Vue.use(Vuex);

    const AppComponent = Vue.extend(App);

    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
    const initial_state = ({
        user_display_name,
        project_id,
        project_name,
        milestone_id,
        milestone_title,
        user_can_create_campaign,
        testdefinition_tracker_id,
        testdefinition_tracker_name,
        expand_backlog_item_id,
        highlight_test_definition_id,
    } as unknown) as RootState;

    new AppComponent({
        store: createStore(initial_state),
    }).$mount(vue_mount_point);
});
