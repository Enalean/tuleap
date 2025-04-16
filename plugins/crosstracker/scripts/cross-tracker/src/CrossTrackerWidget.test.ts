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
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import { EMITTER, IS_USER_ADMIN, WIDGET_TITLE_UPDATER } from "./injection-symbols";
import CreateNewQuery from "./components/query/creation/CreateNewQuery.vue";
import ReadQuery from "./components/ReadQuery.vue";
import { WidgetTitleUpdater } from "./WidgetTitleUpdater";
import type { Emitter } from "mitt";
import mitt from "mitt";
import type { Events } from "./helpers/widget-events";
import {
    EDIT_QUERY_EVENT,
    INITIALIZED_WITH_QUERY_EVENT,
    NEW_QUERY_CREATED_EVENT,
    QUERY_EDITED_EVENT,
    SWITCH_QUERY_EVENT,
} from "./helpers/widget-events";
import EditQuery from "./components/query/edition/EditQuery.vue";

vi.useFakeTimers();

describe("CrossTrackerWidget", () => {
    let is_user_admin: boolean;
    let widget_title_element: HTMLSpanElement;
    let emitter: Emitter<Events>;

    beforeEach(() => {
        emitter = mitt<Events>();
        widget_title_element = document.createElement("span");
        widget_title_element.textContent = "Cross trackers search";
        is_user_admin = true;
    });

    function getWrapper(): VueWrapper<InstanceType<typeof CrossTrackerWidget>> {
        return shallowMount(CrossTrackerWidget, {
            global: {
                provide: {
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                    [EMITTER.valueOf()]: emitter,
                    [WIDGET_TITLE_UPDATER.valueOf()]: WidgetTitleUpdater(
                        emitter,
                        widget_title_element,
                        "Cross trackers search",
                    ),
                },
            },
        });
    }

    describe("Pane displayed", () => {
        it("Displays the read query pane by default", () => {
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ReadQuery).exists()).toBe(true);
            expect(wrapper.findComponent(CreateNewQuery).exists()).toBe(false);
            expect(wrapper.findComponent(EditQuery).exists()).toBe(false);
        });

        it("Displays the creation query pane at create new query event", async () => {
            const wrapper = getWrapper();

            wrapper.findComponent(ReadQuery).vm.$emit("switch-to-create-query-pane");
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(ReadQuery).exists()).toBe(false);
            expect(wrapper.findComponent(CreateNewQuery).exists()).toBe(true);
            expect(wrapper.findComponent(EditQuery).exists()).toBe(false);
        });

        it("Displays the edit query pane at create new query event", async () => {
            const wrapper = getWrapper();

            emitter.emit(EDIT_QUERY_EVENT, {
                query: {
                    id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
                    tql_query: "SELECT @pretty_title FROM @project = 'self' WHERE @id > 15",
                    title: "Some artifacts",
                    description: "a query",
                    is_default: false,
                },
            });
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(ReadQuery).exists()).toBe(false);
            expect(wrapper.findComponent(CreateNewQuery).exists()).toBe(false);
            expect(wrapper.findComponent(EditQuery).exists()).toBe(true);
        });
    });

    describe(`Reacts on
        INITIALIZED_WITH_QUERY_EVENT,
        SWITCH_QUERY_EVENT,
        NEW_QUERY_CREATED_EVENT,
        QUERY_EDITED_EVENT,
    `, () => {
        it.each([
            INITIALIZED_WITH_QUERY_EVENT,
            SWITCH_QUERY_EVENT,
            NEW_QUERY_CREATED_EVENT,
            QUERY_EDITED_EVENT,
        ])("sets the currently selected query on %s", async (event: string) => {
            const wrapper = getWrapper();
            const selected_query = {
                id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
                tql_query: "SELECT @pretty_title FROM @project = 'self' WHERE @id > 15",
                title: "Some artifacts",
                description: "a query",
                is_default: false,
            };

            emitter.emit(event as keyof Events, { query: selected_query });

            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(ReadQuery).props().selected_query).toStrictEqual(
                selected_query,
            );
        });
    });
});
