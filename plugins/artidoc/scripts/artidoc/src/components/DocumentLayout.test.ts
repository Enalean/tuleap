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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentLayout from "./DocumentLayout.vue";
import DocumentContent from "./DocumentContent.vue";
import DocumentSidebar from "./sidebar/DocumentSidebar.vue";

describe("DocumentLayout", () => {
    function getWrapper(): VueWrapper {
        return shallowMount(DocumentLayout);
    }
    it("should display document content", () => {
        expect(getWrapper().findComponent(DocumentContent).exists()).toBe(true);
    });

    it("should display sidebar", () => {
        expect(getWrapper().findComponent(DocumentSidebar).exists()).toBe(true);
    });
});
