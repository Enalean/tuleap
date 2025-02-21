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
import { EMITTER, IS_MULTIPLE_QUERY_SUPPORTED, IS_USER_ADMIN } from "./injection-symbols";
import { EmitterStub } from "../tests/stubs/EmitterStub";
import CreateNewQuery from "./components/query/creation/CreateNewQuery.vue";
import ReadQuery from "./components/ReadQuery.vue";

vi.useFakeTimers();

describe("CrossTrackerWidget", () => {
    let is_user_admin: boolean;

    beforeEach(() => {
        is_user_admin = true;
    });

    function getWrapper(): VueWrapper<InstanceType<typeof CrossTrackerWidget>> {
        return shallowMount(CrossTrackerWidget, {
            global: {
                provide: {
                    [IS_USER_ADMIN.valueOf()]: is_user_admin,
                    [EMITTER.valueOf()]: EmitterStub(),
                    [IS_MULTIPLE_QUERY_SUPPORTED.valueOf()]: true,
                },
            },
        });
    }

    describe("Pane displayed", () => {
        it("Displays the read query pane by default", () => {
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ReadQuery).exists()).toBe(true);
            expect(wrapper.findComponent(CreateNewQuery).exists()).toBe(false);
        });

        it("Displays the creation query pane at create new query event", async () => {
            const wrapper = getWrapper();

            wrapper.findComponent(ReadQuery).vm.$emit("switch-to-create-query-pane");
            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.findComponent(ReadQuery).exists()).toBe(false);
            expect(wrapper.findComponent(CreateNewQuery).exists()).toBe(true);
        });
    });
});
