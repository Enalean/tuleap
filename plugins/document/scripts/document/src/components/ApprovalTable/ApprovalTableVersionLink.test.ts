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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import ApprovalTableVersionLink from "./ApprovalTableVersionLink.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { ApprovalTableBuilder } from "../../../tests/builders/ApprovalTableBuilder";

describe(ApprovalTableVersionLink, () => {
    it("show the label version by default", () => {
        const wrapper = shallowMount(ApprovalTableVersionLink, {
            props: {
                table: new ApprovalTableBuilder(35)
                    .withVersionId(3)
                    .withVersionLabel("v1.0")
                    .build(),
                label: "Document version",
            },
            global: getGlobalTestOptions({}),
        });

        expect(wrapper.find("[data-test=version-link]").text()).toBe("v1.0");
        expect(wrapper.find("[data-test=version-not-found]").exists()).toBe(false);
    });

    it("should fallback to version number when version does not have a label", () => {
        const wrapper = shallowMount(ApprovalTableVersionLink, {
            props: {
                table: new ApprovalTableBuilder(35).withVersionId(5).withVersionNumber(5).build(),
                label: "Document version",
            },
            global: getGlobalTestOptions({}),
        });

        expect(wrapper.find("[data-test=version-link]").text()).toBe("5");
        expect(wrapper.find("[data-test=version-not-found]").exists()).toBe(false);
    });

    it("shows empty state when nothing is defined", () => {
        const wrapper = shallowMount(ApprovalTableVersionLink, {
            props: { table: new ApprovalTableBuilder(35).build(), label: "Document version" },
            global: getGlobalTestOptions({}),
        });

        expect(wrapper.find("[data-test=version-link]").exists()).toBe(false);
        expect(wrapper.find("[data-test=version-not-found]").text()).toBe("Version not found");
    });
});
