/*
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

import { describe, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import FieldLinks from "./FieldLinks.vue";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import type { ReadonlyFieldLinkedArtifact } from "@/sections/readonly-fields/ReadonlyFields";
import { LinkedArtifactStub } from "@/sections/stubs/readonly-fields/LinkedArtifactStub";
import { PROJECT_ID } from "@/project-id-injection-key";

describe("FieldLinks", () => {
    const getWrapper = (values: ReadonlyFieldLinkedArtifact[]): VueWrapper =>
        shallowMount(FieldLinks, {
            props: {
                field: ReadonlyFieldStub.linkField(values),
            },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [PROJECT_ID.valueOf()]: 156,
                },
            },
        });

    it("When no artifact is linked, then it should display an empty state", () => {
        const wrapper = getWrapper([]);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("Should display the linked artifacts", () => {
        const wrapper = getWrapper([
            LinkedArtifactStub.override({
                status: { label: "On going", is_open: true, color: "" },
            }),
            LinkedArtifactStub.override({
                status: { label: "Done", is_open: false, color: "surf-green" },
            }),
        ]);

        expect(wrapper.findAll("[data-test=linked-artifact]")).toHaveLength(2);

        const [first_link, second_link] = wrapper.findAll("[data-test=linked-artifact]");

        const first_link_status = first_link.find("[data-test=linked-artifact-status]");

        expect(first_link_status.exists()).toBe(true);
        expect(first_link_status.classes()).toContain("tlp-badge-secondary");

        const second_link_status = second_link.find("[data-test=linked-artifact-status]");

        expect(second_link_status.exists()).toBe(true);
        expect(second_link_status.classes()).toContain("tlp-badge-surf-green");
    });
});
