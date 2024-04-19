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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "@/App.vue";
import * as rest from "./helpers/rest-querier";
import ArtidocSectionFactory from "@/helpers/artidoc-section.factory";
import { okAsync } from "neverthrow";
import type { ComponentPublicInstance } from "vue";
import DocumentView from "@/views/DocumentView.vue";
import DocumentViewSkeleton from "@/views/DocumentViewSkeleton.vue";

vi.mock("./rest-querier");

describe("App", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;
    beforeEach(() => {
        wrapper = shallowMount(App, {
            props: {
                item_id: 1,
            },
        });
    });
    describe("when sections are loading", () => {
        it("should display skeleton view", () => {
            expect(wrapper.findComponent(DocumentView).exists()).toBe(false);
            expect(wrapper.findComponent(DocumentViewSkeleton).exists()).toBe(true);
        });
    });

    describe("when sections are loaded", () => {
        it("should display document view", async () => {
            vi.spyOn(rest, "getAllSections").mockReturnValue(
                okAsync([ArtidocSectionFactory.create()]),
            );

            await wrapper.vm.$nextTick();
            await wrapper.vm.$nextTick();

            expect(wrapper.findComponent(DocumentView).exists()).toBe(true);
            expect(wrapper.findComponent(DocumentViewSkeleton).exists()).toBe(false);
        });
    });
});
