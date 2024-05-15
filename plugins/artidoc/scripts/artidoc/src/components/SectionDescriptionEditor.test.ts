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
import { beforeAll, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SectionDescriptionEditor from "@/components/SectionDescriptionEditor.vue";
import type { ComponentPublicInstance } from "vue";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { CURRENT_LOCALE } from "@/locale-injection-key";
import { userLocale } from "@/helpers/user-locale";

vi.mock("@tuleap/vue-strict-inject");

describe("SectionDescriptionEditor", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation((key) => {
            if (key === CURRENT_LOCALE) {
                return userLocale("fr_FR");
            }
        });

        wrapper = shallowMount(SectionDescriptionEditor, {
            props: {
                artifact_id: 1,
                editable_description: "<h1>description</h1>",
                input_current_description: vi.fn(),
            },
        });
    });

    it("should display the editor", () => {
        expect(wrapper.find("ckeditor").exists()).toBe(true);
    });
});
