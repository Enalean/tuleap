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

import { beforeAll, describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { mount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import StateMessageWithImage from "@/components/StateMessageWithImage.vue";
import type { ComponentPublicInstance } from "vue";
import NoAccessState from "@/views/NoAccessState.vue";
import RestrictedDocumentIllustration from "@/assets/RestrictedDocumentIllustration.vue";

describe("NoAccessState", () => {
    let wrapper: VueWrapper<ComponentPublicInstance>;

    beforeAll(() => {
        wrapper = mount(NoAccessState, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    });

    it("should display the error message", () => {
        expect(wrapper.findComponent(StateMessageWithImage).exists()).toBe(true);
    });
    it("should display a restricted document illustration", () => {
        expect(wrapper.findComponent(RestrictedDocumentIllustration).exists()).toBe(true);
    });
});
