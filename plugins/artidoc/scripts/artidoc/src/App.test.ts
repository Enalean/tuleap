/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import EmptyState from "@/views/EmptyState.vue";
import { createGettext } from "vue3-gettext";
import DocumentContent from "@/views/DocumentContent.vue";
import App from "@/App.vue";
import * as rest from "./helpers/rest-querier";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

vi.mock("./rest-querier");

describe("App", () => {
    describe("when sections not found", () => {
        it("should display empty state view", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                errAsync(Fault.fromMessage("sections not found")),
            );

            const wrapper = shallowMount(App, {
                global: {
                    plugins: [createGettext({ silent: true })],
                },
                props: {
                    item_id: 1,
                },
            });

            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(EmptyState).exists()).toBe(true);
        });
    });

    describe("when sections found", () => {
        it("should display document content view", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([ArtidocSectionFactory.create()]),
            );

            const wrapper = shallowMount(App, {
                global: {
                    plugins: [createGettext({ silent: true })],
                },
                props: {
                    item_id: 1,
                },
            });

            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(DocumentContent).exists()).toBe(true);
        });
    });
});
