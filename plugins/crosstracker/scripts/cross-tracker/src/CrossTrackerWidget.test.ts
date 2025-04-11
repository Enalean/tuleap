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
import CrossTrackerWidget from "./CrossTrackerWidget.vue";
import {
    DEFAULT_WIDGET_TITLE,
    EMITTER,
    IS_USER_ADMIN,
    UPDATE_WIDGET_TITLE,
} from "./injection-symbols";
import CreateNewQuery from "./components/query/creation/CreateNewQuery.vue";
import ReadQuery from "./components/ReadQuery.vue";
import { WidgetTitleUpdater } from "./WidgetTitleUpdater";
import type { Emitter } from "mitt";
import mitt from "mitt";
import type { Events, UpdateWidgetTitleEvent } from "./helpers/widget-events";
import {
    SWITCH_QUERY_EVENT,
    EDIT_QUERY_EVENT,
    INITIALIZED_WITH_QUERY_EVENT,
    UPDATE_WIDGET_TITLE_EVENT,
    NEW_QUERY_CREATED_EVENT,
    QUERY_EDITED_EVENT,
} from "./helpers/widget-events";
import EditQuery from "./components/query/edition/EditQuery.vue";

vi.useFakeTimers();

describe("CrossTrackerWidget", () => {
    let is_user_admin: boolean;
    let widget_title_element: HTMLSpanElement;
    let dispatched_update_widget_title_event: UpdateWidgetTitleEvent[];
    let emitter: Emitter<Events>;

    const registerDispatchedUpdateWidgetTitleEvent = (event: UpdateWidgetTitleEvent): void => {
        dispatched_update_widget_title_event.push(event);
    };

    beforeEach(() => {
        emitter = mitt<Events>();
        dispatched_update_widget_title_event = [];
        widget_title_element = document.createElement("span");
        widget_title_element.textContent = "Cross trackers search";
        is_user_admin = true;

        emitter.on(UPDATE_WIDGET_TITLE_EVENT, registerDispatchedUpdateWidgetTitleEvent);
    });

    afterEach(() => {
        emitter.off(UPDATE_WIDGET_TITLE_EVENT, registerDispatchedUpdateWidgetTitleEvent);
    });

    function getWrapper(): VueWrapper<InstanceType<typeof CrossTrackerWidget>> {
        return shallowMount(CrossTrackerWidget, {
            global: {
                provide: {
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                    [EMITTER.valueOf()]: emitter,
                    [UPDATE_WIDGET_TITLE.valueOf()]: WidgetTitleUpdater(
                        emitter,
                        widget_title_element,
                    ),
                    [DEFAULT_WIDGET_TITLE.valueOf()]: "Cross Tracker Search",
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
                query_to_edit: {
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
        INITIALIZE_WITH_QUERY_EVENT,
        SWITCH_QUERY_EVENT,
        NEW_QUERY_CREATED_EVENT,
        QUERY_EDITED_EVENT
    `, () => {
        it.each([
            INITIALIZED_WITH_QUERY_EVENT,
            SWITCH_QUERY_EVENT,
            NEW_QUERY_CREATED_EVENT,
            QUERY_EDITED_EVENT,
        ])("emits an UPDATE_WIDGET_TITLE_EVENT on %s", (event: string) => {
            getWrapper();
            const title = "Some artifacts";

            emitter.emit(event as keyof Events, {
                query: {
                    id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
                    tql_query: "SELECT @pretty_title FROM @project = 'self' WHERE @id > 15",
                    title,
                    description: "a query",
                    is_default: false,
                },
            });

            expect(dispatched_update_widget_title_event).toHaveLength(1);
            expect(dispatched_update_widget_title_event[0].new_title).toBe(title);
        });
    });
});
