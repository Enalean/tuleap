/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { DeletedQueryEvent, Events, NotifyFaultEvent } from "../../helpers/widget-events";
import { NOTIFY_FAULT_EVENT, QUERY_DELETED_EVENT } from "../../helpers/widget-events";
import type { Emitter } from "mitt";
import mitt from "mitt";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DeleteQueryButton from "./DeleteQueryButton.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { EMITTER } from "../../injection-symbols";
import type { Query } from "../../type";
import * as rest_querier from "../../api/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

vi.useFakeTimers();

describe("DeleteQueryButton", () => {
    let current_query: Query;
    let emitter: Emitter<Events>;
    let dispatched_fault_events: NotifyFaultEvent[];
    let dispatched_delete_events: DeletedQueryEvent[];

    const registerFaultEvent = (event: NotifyFaultEvent): void => {
        dispatched_fault_events.push(event);
    };

    const registerDeleteEvents = (event: DeletedQueryEvent): void => {
        dispatched_delete_events.push(event);
    };

    beforeEach(() => {
        current_query = {
            id: "00000000-03e8-70c0-9e41-6ea7a4e2b78d",
            tql_query: "",
            title: "",
            description: "a great backend query",
            is_default: false,
        };
        emitter = mitt<Events>();
        dispatched_fault_events = [];
        dispatched_delete_events = [];
        emitter.on(NOTIFY_FAULT_EVENT, registerFaultEvent);
        emitter.on(QUERY_DELETED_EVENT, registerDeleteEvents);
    });

    afterEach(() => {
        emitter.off(NOTIFY_FAULT_EVENT, registerFaultEvent);
        emitter.off(QUERY_DELETED_EVENT, registerDeleteEvents);
    });

    const getWrapper = (): VueWrapper<InstanceType<typeof DeleteQueryButton>> => {
        return shallowMount(DeleteQueryButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [EMITTER.valueOf()]: emitter,
                },
            },
            props: {
                current_query,
            },
        });
    };

    it("Shows the modal and then send the delete event", async () => {
        const delete_query = vi.spyOn(rest_querier, "deleteQuery").mockReturnValue(okAsync(null));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=delete-query-button]").trigger("click");
        await wrapper.find("[data-test=delete-modal-button]").trigger("click");

        expect(delete_query).toHaveBeenCalled();
        expect(dispatched_delete_events).toHaveLength(1);
        expect(dispatched_delete_events[0]).toStrictEqual({ deleted_query: current_query });
    });

    it("Shows the modal and display error", async () => {
        const fault = Fault.fromMessage("Query not found");
        const delete_query = vi.spyOn(rest_querier, "deleteQuery").mockReturnValue(errAsync(fault));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=delete-query-button]").trigger("click");
        await wrapper.find("[data-test=delete-modal-button]").trigger("click");

        expect(delete_query).toHaveBeenCalled();
        expect(dispatched_fault_events).toHaveLength(1);
    });

    it("Shows the modal and do nothing", async () => {
        const delete_query = vi.spyOn(rest_querier, "deleteQuery");
        const wrapper = getWrapper();

        await wrapper.find("[data-test=delete-query-button]").trigger("click");
        await wrapper.find("[data-test=cancel-modal-button]").trigger("click");

        expect(delete_query).not.toHaveBeenCalled();
        expect(dispatched_delete_events).toHaveLength(0);
        expect(dispatched_fault_events).toHaveLength(0);
    });
});
