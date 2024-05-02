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
import { ref } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import App from "@/App.vue";
import type { ComponentPublicInstance } from "vue";
import DocumentView from "@/views/DocumentView.vue";
import * as sectionsStore from "@/stores/useSectionsStore";

vi.mock("./rest-querier");
describe("App", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;
    beforeEach(() => {
        vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue({
            setIsSectionsLoading: vi.fn(),
            setSections: vi.fn(),
            is_sections_loading: ref(false),
            sections: ref([]),
        });
        wrapper = shallowMount(App, {
            props: {
                item_id: 1,
            },
        });
    });
    it("should display the document view", () => {
        expect(wrapper.findComponent(DocumentView).exists()).toBe(true);
    });
});
