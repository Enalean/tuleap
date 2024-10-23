/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { WritingCrossTrackerReport } from "../domain/WritingCrossTrackerReport";
import { ProjectInfoStub } from "../../tests/stubs/ProjectInfoStub";
import { TrackerInfoStub } from "../../tests/stubs/TrackerInfoStub";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import { expect, describe, it } from "vitest";
import EmptyState from "./EmptyState.vue";

describe("EmptyState", () => {
    const getWrapper = (
        writing_cross_tracker_report: WritingCrossTrackerReport,
    ): VueWrapper<InstanceType<typeof EmptyState>> => {
        return shallowMount(EmptyState, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                writing_cross_tracker_report,
            },
        });
    };

    it(`invites the user to create a new query and add trackers
        when nothing is selected`, () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();

        const wrapper = getWrapper(writing_cross_tracker_report);

        expect(wrapper.find("[data-test=selectable-empty-state-title]").text()).toContain(
            "Query is empty",
        );
        expect(wrapper.find("[data-test=selectable-empty-state-text]").text()).toContain(
            "Please create a new query by clicking",
        );
    });

    it(`invites the user to create a new query
        when a the query is empty
        and at least one tracker is selected`, () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.addTracker(
            ProjectInfoStub.withId(116),
            TrackerInfoStub.withId(186),
        );

        const wrapper = getWrapper(writing_cross_tracker_report);

        expect(wrapper.find("[data-test=selectable-empty-state-title]").text()).toContain(
            "Query is empty",
        );
        expect(wrapper.find("[data-test=selectable-empty-state-text]").text()).toContain(
            "Please create a new query",
        );
    });

    it(`invites the user to add tracker
        when a the query is given
        and no tracker is selected`, () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = `SELECT start_date WHERE start_date != ''`;

        const wrapper = getWrapper(writing_cross_tracker_report);

        expect(wrapper.find("[data-test=selectable-empty-state-title]").text()).toContain(
            "No artifact found",
        );
        expect(wrapper.find("[data-test=selectable-empty-state-text]").text()).toContain(
            "Please add trackers by clicking",
        );
    });

    it(`invites the user to update the query
        when a the query is given
        and some trackers are selected`, () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = `SELECT start_date WHERE start_date != ''`;
        writing_cross_tracker_report.addTracker(
            ProjectInfoStub.withId(116),
            TrackerInfoStub.withId(186),
        );
        const wrapper = getWrapper(writing_cross_tracker_report);

        expect(wrapper.find("[data-test=selectable-empty-state-title]").text()).toContain(
            "No artifact found",
        );
        expect(wrapper.find("[data-test=selectable-empty-state-text]").text()).toContain(
            "There is no artifact matching the query",
        );
    });

    it(`display no artifact message if expert mode when no tracker`, () => {
        const writing_cross_tracker_report = new WritingCrossTrackerReport();
        writing_cross_tracker_report.expert_query = `SELECT start_date FROM @project='self' WHERE start_date != ''`;
        writing_cross_tracker_report.expert_mode = true;
        const wrapper = getWrapper(writing_cross_tracker_report);
        expect(wrapper.find("[data-test=selectable-empty-state-title]").text()).toContain(
            "No artifact found",
        );
        expect(wrapper.find("[data-test=selectable-empty-state-text]").text()).toContain(
            "There is no artifact matching the query.",
        );
    });
});
