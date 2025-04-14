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

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import * as rest_querier from "../api/rest-querier";
import ReadingMode from "../components/reading-mode/ReadingMode.vue";
import { EMITTER, IS_USER_ADMIN, WIDGET_ID } from "../injection-symbols";
import ReadQuery from "./ReadQuery.vue";
import type {
    Events,
    InitializedWithQueryEvent,
    NotifyFaultEvent,
    RefreshArtifactsEvent,
    SwitchQueryEvent,
} from "../helpers/widget-events";
import {
    INITIALIZED_WITH_QUERY_EVENT,
    NOTIFY_FAULT_EVENT,
    QUERY_DELETED_EVENT,
    REFRESH_ARTIFACTS_EVENT,
    SWITCH_QUERY_EVENT,
    TOGGLE_QUERY_DETAILS_EVENT,
} from "../helpers/widget-events";
import type { Query } from "../type";
import type { Emitter } from "mitt";
import mitt from "mitt";

vi.useFakeTimers();

describe("ReadQuery", () => {
    let is_user_admin: boolean;
    let dispatched_switch_query_events: SwitchQueryEvent[];
    let dispatched_initialized_with_query_events: InitializedWithQueryEvent[];
    let dispatched_fault_events: NotifyFaultEvent[];
    let dispatched_refresh_events: RefreshArtifactsEvent[];
    let emitter: Emitter<Events>;
    let selected_query: Query | undefined;

    const registerInitializedWithQueryEvent = (event: InitializedWithQueryEvent): void => {
        dispatched_initialized_with_query_events.push(event);
    };
    const registerSwitchQueryEvent = (event: SwitchQueryEvent): void => {
        dispatched_switch_query_events.push(event);
    };

    const registerFaultEvent = (event: NotifyFaultEvent): void => {
        dispatched_fault_events.push(event);
    };

    const registerRefreshArtifactsEvent = (event: RefreshArtifactsEvent): void => {
        dispatched_refresh_events.push(event);
    };

    beforeEach(() => {
        selected_query = undefined;
        is_user_admin = true;
        dispatched_switch_query_events = [];
        dispatched_initialized_with_query_events = [];
        dispatched_fault_events = [];
        dispatched_refresh_events = [];
        emitter = mitt<Events>();
        emitter.on(SWITCH_QUERY_EVENT, registerSwitchQueryEvent);
        emitter.on(INITIALIZED_WITH_QUERY_EVENT, registerInitializedWithQueryEvent);
        emitter.on(NOTIFY_FAULT_EVENT, registerFaultEvent);
        emitter.on(REFRESH_ARTIFACTS_EVENT, registerRefreshArtifactsEvent);

        vi.spyOn(rest_querier, "getQueries").mockReturnValue(
            okAsync([
                {
                    id: "0194dfd6-a489-703b-aabd-9d473212d908",
                    tql_query: "SELECT @id FROM @project = 'self' WHERE @id >= 1",
                    title: "My title",
                    description: "",
                    is_default: false,
                },
            ]),
        );
    });

    afterEach(() => {
        emitter.off(SWITCH_QUERY_EVENT, registerSwitchQueryEvent);
        emitter.off(INITIALIZED_WITH_QUERY_EVENT, registerInitializedWithQueryEvent);
        emitter.off(NOTIFY_FAULT_EVENT, registerFaultEvent);
        emitter.off(REFRESH_ARTIFACTS_EVENT, registerRefreshArtifactsEvent);
    });

    function getWrapper(): VueWrapper<InstanceType<typeof ReadQuery>> {
        return shallowMount(ReadQuery, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [WIDGET_ID.valueOf()]: 96,
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                selected_query,
            },
        });
    }

    describe("loadBackendQueries()", () => {
        it("When I load the widget, then the queries will be initialized", async () => {
            const query = 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""';
            const uuid = "0194dfd6-a489-703b-aabd-9d473212d908";
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                okAsync([
                    {
                        tql_query: query,
                        title: " TQL query title",
                        description: "",
                        id: uuid,
                        is_default: false,
                    },
                ]),
            );
            getWrapper();
            await vi.runOnlyPendingTimersAsync();
        });

        it("When there is a REST error, it will be shown", async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                errAsync(Fault.fromMessage("Query 41 not found")),
            );
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_fault_events).toHaveLength(1);
            expect(dispatched_fault_events[0].fault.isQueryRetrieval()).toBe(true);
        });

        it("Force creation mode when widget has no query", async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync([]));
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.emitted()).toHaveProperty("switch-to-create-query-pane");
        });

        it("Does not emit an INITIALIZED_WITH_QUERY_EVENT when there are no queries", () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync([]));
            getWrapper();

            expect(dispatched_initialized_with_query_events.length).toBe(0);
        });

        it("Does emit an INITIALIZED_WITH_QUERY_EVENT with the first query as parameter once done loading", async () => {
            const query = 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""';
            const uuid1 = "0194dfd6-a489-703b-aabd-9d473212d908";
            const uuid2 = "01952813-7ae7-7a27-bcc0-4a9c660dccb4";
            const queries: ReadonlyArray<Query> = [
                {
                    tql_query: query,
                    title: "TQL query title 1",
                    description: "",
                    id: uuid1,
                    is_default: false,
                },
                {
                    tql_query: query,
                    title: "TQL query title 2",
                    description: "",
                    id: uuid2,
                    is_default: false,
                },
            ];
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync(queries));
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_initialized_with_query_events.length).toBe(1);
            expect(dispatched_initialized_with_query_events[0].query).toStrictEqual(queries[0]);
        });

        it("Does emit a INITIALIZED_WITH_QUERY_EVENT with the selected query as parameter if it is defined", async () => {
            const query = 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""';

            const uuid_previously_selected = "01952813-7ae7-7a27-bcc0-4a9c660dccb4";
            const previously_selected_query = {
                tql_query: query,
                title: "Preselected TQL query",
                description: "",
                id: uuid_previously_selected,
                is_default: false,
            };
            selected_query = previously_selected_query;

            const uuid_query = "0194dfd6-a489-703b-aabd-9d473212d908";
            const queries: ReadonlyArray<Query> = [
                {
                    tql_query: query,
                    title: "TQL query title 1",
                    description: "",
                    id: uuid_query,
                    is_default: false,
                },
            ];
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync(queries));
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(dispatched_initialized_with_query_events.length).toBe(1);
            expect(dispatched_initialized_with_query_events[0].query).toStrictEqual(
                previously_selected_query,
            );
        });
    });

    describe(`isXLSXExportAllowed`, () => {
        it(`when there was an error, it does not allow XLSX export`, async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                errAsync(Fault.fromMessage("Oops an error")),
            );
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.is_export_allowed).toBe(false);
        });

        it(`when user is NOT admin and there is no error,
            it allows XLSX export`, () => {
            is_user_admin = false;
            const wrapper = getWrapper();

            expect(wrapper.vm.is_export_allowed).toBe(true);
        });

        it(`when user is admin and there is an error selected in the query,
            it does not allow XLSX export`, async () => {
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(
                errAsync(Fault.fromMessage("Ooops an error")),
            );

            const wrapper = getWrapper();

            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.vm.is_export_allowed).toBe(false);
        });

        it(`when user is admin and there are no invalid trackers,
            it allows xlsx export`, () => {
            const wrapper = getWrapper();

            expect(wrapper.vm.is_export_allowed).toBe(true);
        });
    });

    describe("areQueryDetailsShown()", () => {
        it("should not display query details if multiple queries are enabled but details are not toggled", async () => {
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(ReadingMode).exists()).toBe(false);
        });

        it("should display query details if multiple queries are enabled and details are toggled", async () => {
            const wrapper = getWrapper();
            emitter.emit(TOGGLE_QUERY_DETAILS_EVENT, { display_query_details: true });
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(ReadingMode).exists()).toBe(true);
        });
    });

    describe("handleDeleteQuery", () => {
        it("delete the event query and then display the other one", async () => {
            const query = 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""';
            const query_1 = {
                tql_query: query,
                title: "TQL query title 1",
                description: "",
                id: "0194dfd6-a489-703b-aabd-9d473212d908",
                is_default: false,
            };
            const query_2 = {
                tql_query: query,
                title: "TQL query title 2",
                description: "",
                id: "01952813-7ae7-7a27-bcc0-4a9c660dccb4",
                is_default: false,
            };
            const queries: ReadonlyArray<Query> = [query_1, query_2];
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync(queries));
            getWrapper();
            await vi.runOnlyPendingTimersAsync();
            emitter.emit(QUERY_DELETED_EVENT, { deleted_query: query_1 });

            expect(dispatched_switch_query_events).toHaveLength(1);
            expect(dispatched_switch_query_events[0]).toStrictEqual({ query: query_2 });
            expect(dispatched_refresh_events).toHaveLength(1);
            expect(dispatched_refresh_events[0]).toStrictEqual({ query: query_2 });
        });

        it("delete the event query and then display the creation pane", async () => {
            const query = {
                tql_query: 'SELECT @title FROM @project.name="TATAYO" WHERE @title != ""',
                title: "TQL query title 1",
                description: "",
                id: "0194dfd6-a489-703b-aabd-9d473212d908",
                is_default: false,
            };
            vi.spyOn(rest_querier, "getQueries").mockReturnValue(okAsync([query]));
            const wrapper = getWrapper();
            await vi.runOnlyPendingTimersAsync();
            emitter.emit(QUERY_DELETED_EVENT, { deleted_query: query });

            expect(wrapper.emitted("switch-to-create-query-pane")).toBeDefined();
        });
    });
});
