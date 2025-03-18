/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { errAsync, okAsync } from "neverthrow";
import { ref } from "vue";
import { Fault } from "@tuleap/fault";
import ReadingMode from "./ReadingMode.vue";
import * as rest_querier from "../../api/rest-querier";
import type { Query } from "../../type";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import {
    EMITTER,
    IS_MULTIPLE_QUERY_SUPPORTED,
    IS_USER_ADMIN,
    REPORT_STATE,
    WIDGET_ID,
} from "../../injection-symbols";
import type {
    EmitterProvider,
    Events,
    NotifyFaultEvent,
    RefreshArtifactsEvent,
} from "../../helpers/emitter-provider";
import { NOTIFY_FAULT_EVENT, REFRESH_ARTIFACTS_EVENT } from "../../helpers/emitter-provider";
import mitt from "mitt";

describe("ReadingMode", () => {
    let backend_query: Query,
        reading_query: Query,
        is_user_admin: boolean,
        has_error: boolean,
        emitter: EmitterProvider,
        is_multiple_query_supported: boolean;
    let dispatched_fault_events: NotifyFaultEvent[];
    let dispatched_refresh_events: RefreshArtifactsEvent[];

    beforeEach(() => {
        backend_query = {
            id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            tql_query: "",
            title: "",
            description: "a great backend query",
            is_default: false,
        };
        reading_query = {
            id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            tql_query: "",
            title: "",
            description: "a great reading query",
            is_default: false,
        };
        is_user_admin = true;
        has_error = false;
        is_multiple_query_supported = false;
        emitter = mitt<Events>();
        dispatched_fault_events = [];
        dispatched_refresh_events = [];
        emitter.on(NOTIFY_FAULT_EVENT, (event) => {
            dispatched_fault_events.push(event);
        });
        emitter.on(REFRESH_ARTIFACTS_EVENT, (event) => {
            dispatched_refresh_events.push(event);
        });
    });

    function instantiateComponent(): VueWrapper<InstanceType<typeof ReadingMode>> {
        return shallowMount(ReadingMode, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPORT_STATE.valueOf()]: ref("result-preview"),
                    [WIDGET_ID.valueOf()]: 875,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                    [EMITTER.valueOf()]: emitter,
                    [IS_MULTIPLE_QUERY_SUPPORTED.valueOf()]: is_multiple_query_supported,
                },
            },
            props: {
                has_error,
                backend_query,
                reading_query,
            },
        });
    }

    describe("switchToWritingMode()", () => {
        it("When I switch to the writing mode, then an event will be emitted", () => {
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeDefined();
        });

        it(`Given I am browsing as project member,
            when I try to switch to writing mode, nothing will happen`, () => {
            is_user_admin = false;
            const wrapper = instantiateComponent();

            wrapper.get("[data-test=cross-tracker-reading-mode]").trigger("click");

            const emitted = wrapper.emitted("switch-to-writing-mode");
            expect(emitted).toBeUndefined();
        });
    });

    describe("saveReport()", () => {
        it(`will update the backend report and emit a "saved" event`, async () => {
            const expert_query =
                'SELECT @description FROM @project.name="TOTOYA" WHERE @ddescription != ""';
            const query: Query = {
                id: reading_query.id,
                tql_query: expert_query,
                title: reading_query.title,
                description: reading_query.description,
                is_default: false,
            };

            const updateQuery = vi
                .spyOn(rest_querier, "updateQuery")
                .mockReturnValue(okAsync(query));
            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(updateQuery).toHaveBeenCalled();
            const emitted = wrapper.emitted("saved");
            expect(emitted).toBeDefined();
        });

        it("Given the report is in error, then nothing will happen", async () => {
            has_error = true;
            const updateReport = vi.spyOn(rest_querier, "updateQuery");

            const wrapper = instantiateComponent();
            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(updateReport).not.toHaveBeenCalled();
        });

        it("When there is a REST error, then it will be shown", async () => {
            vi.spyOn(rest_querier, "updateQuery").mockReturnValue(
                errAsync(Fault.fromMessage("Report not found")),
            );

            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-save-report]").trigger("click");

            expect(dispatched_fault_events).toHaveLength(1);
            expect(dispatched_fault_events[0].fault.isSaveReport()).toBe(true);
        });
    });

    describe("cancelReport()", () => {
        it(`when the report is unsaved and I click on "Cancel", then an event will be emitted`, async () => {
            const wrapper = instantiateComponent();

            await wrapper.get("[data-test=cross-tracker-cancel-report]").trigger("click");

            expect(wrapper.emitted("discard-unsaved-report")).toBeDefined();
            expect(dispatched_refresh_events).toHaveLength(1);
            expect(dispatched_refresh_events[0]).toStrictEqual({
                query: backend_query,
            });
        });
    });

    describe("renders query description", () => {
        it("does not show query’s description if multiple query mode is not enabled", () => {
            is_multiple_query_supported = false;

            const wrapper = instantiateComponent();
            expect(wrapper.find("[data-test=query-description]").exists()).toBe(false);
        });

        it("shows query’s description if multiple query mode is enabled", () => {
            is_multiple_query_supported = true;

            const wrapper = instantiateComponent();
            const element = wrapper.find("[data-test=query-description]");

            expect(element.exists()).toBe(true);
            expect(element.text()).toBe("a great reading query");
        });
    });
});
