/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { EMITTER, WIDGET_ID } from "../../injection-symbols";
import { beforeEach, expect, describe, it } from "vitest";
import type {
    Events,
    EmitterProvider,
    ToggleQueryDetailsEvent,
} from "../../helpers/emitter-provider";
import { TOGGLE_QUERY_DETAILS_EVENT } from "../../helpers/emitter-provider";
import QueryDetailsButton from "./QueryDetailsButton.vue";
import mitt from "mitt";

describe("QueryDetailsButton", () => {
    let emitter: EmitterProvider;
    let dispatched_toggle_query_details_events: ToggleQueryDetailsEvent[];
    let are_query_details_toggled: boolean;

    beforeEach(() => {
        are_query_details_toggled = false;
        dispatched_toggle_query_details_events = [];
        emitter = mitt<Events>();
        emitter.on(TOGGLE_QUERY_DETAILS_EVENT, (event) => {
            dispatched_toggle_query_details_events.push(event);
        });
    });
    const getWrapper = (): VueWrapper<InstanceType<typeof QueryDetailsButton>> => {
        return shallowMount(QueryDetailsButton, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [EMITTER.valueOf()]: emitter,
                    [WIDGET_ID.valueOf()]: 34,
                },
            },
            props: {
                are_query_details_toggled: are_query_details_toggled,
            },
        });
    };

    it.each([true, false])(
        "should send an event which switches the toggle state of the query details on click when the initial state of the toggle is %s",
        async (initial_toggle_state) => {
            are_query_details_toggled = initial_toggle_state;

            const wrapper = getWrapper();
            const details_button = wrapper.find<HTMLInputElement>(
                "[data-test=toggle-query-details-input",
            );

            await details_button.setValue(!initial_toggle_state);
            expect(details_button.element.checked).toBe(!initial_toggle_state);
            expect(dispatched_toggle_query_details_events[0].display_query_details).toBe(
                !initial_toggle_state,
            );
        },
    );
});
