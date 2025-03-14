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

import { describe, beforeEach, expect, it } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { Fault } from "@tuleap/fault";
import ReorderArrows from "@/components/sidebar/toc/ReorderArrows.vue";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import type { ReactiveStoredArtidocSection } from "@/sections/SectionsCollection";
import { DOCUMENT_ID } from "@/document-id-injection-key";
import type { SectionsReorderer } from "@/sections/reorder/SectionsReorderer";
import { SectionsReordererStub } from "@/sections/stubs/SectionsReordererStub";
import { ReactiveStoredArtidocSectionStub } from "@/sections/stubs/ReactiveStoredArtidocSectionStub";
import type { SectionsStructurer } from "@/sections/reorder/SectionsStructurer";
import { getSectionsStructurer } from "@/sections/reorder/SectionsStructurer";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";

describe("ReorderArrows", () => {
    let section: ReactiveStoredArtidocSection,
        sections_reorderer: SectionsReorderer,
        sections_structurer: SectionsStructurer;

    beforeEach(() => {
        section = ReactiveStoredArtidocSectionStub.fromSection(ArtifactSectionFactory.create());
        sections_reorderer = SectionsReordererStub.withGreatSuccess();
        sections_structurer = getSectionsStructurer(
            SectionsCollectionStub.fromReactiveStoredArtifactSections([section]),
        );
    });

    function getWrapper({
        is_first,
        is_last,
    }: {
        is_first: boolean;
        is_last: boolean;
    }): VueWrapper {
        return shallowMount(ReorderArrows, {
            props: {
                is_first,
                is_last,
                section,
                sections_reorderer,
                sections_structurer,
            },
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [DOCUMENT_ID.valueOf()]: 123,
                },
            },
        });
    }

    it("should display two move buttons for a section", () => {
        const wrapper = getWrapper({ is_first: false, is_last: false });

        const up_button = wrapper.find("[data-test=move-up]");
        const down_button = wrapper.find("[data-test=move-down]");

        expect(up_button.exists()).toBe(true);
        expect(down_button.exists()).toBe(true);
    });

    it("should display one move button for the first section", () => {
        const wrapper = getWrapper({ is_first: true, is_last: false });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(false);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(true);
    });

    it("should display one move button for the last section", () => {
        const wrapper = getWrapper({ is_first: false, is_last: true });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(true);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(false);
    });

    it("should NOT display any move button when there is only one section", () => {
        const wrapper = getWrapper({ is_first: true, is_last: true });

        expect(wrapper.find("[data-test=move-up]").exists()).toBe(false);
        expect(wrapper.find("[data-test=move-down]").exists()).toBe(false);
    });

    describe("onclick", () => {
        it.each([["move-up"], ["move-down"]])(
            "When the %s button is clicked, then it should emit a moving-section-up-or-down event",
            (button_name) => {
                const wrapper = getWrapper({ is_first: false, is_last: false });

                wrapper.find(`[data-test=${button_name}]`).trigger("click");

                const event = wrapper.emitted("moving-section-up-or-down");
                if (!event) {
                    throw new Error("Expected a moving-section-up-or-down event");
                }

                expect(event[0][0]).toStrictEqual([section.value]);
            },
        );

        it.each([["move-up"], ["move-down"]])(
            "When the section has been %s successfully, then it should emit a moved-section-up-or-down event",
            async (button_name) => {
                const wrapper = getWrapper({ is_first: false, is_last: false });

                await wrapper.find(`[data-test=${button_name}]`).trigger("click");

                const event = wrapper.emitted("moved-section-up-or-down");
                if (!event) {
                    throw new Error("Expected a moved-section-up-or-down event");
                }

                expect(event[0][0]).toStrictEqual([section.value]);
            },
        );

        it.each([["move-up"], ["move-down"]])(
            "when the section %s unsuccessfully, then it should emit the moved-section-up-or-down-fault, ",
            async (button_name) => {
                const fault = Fault.fromMessage("Great Scott!");
                sections_reorderer = SectionsReordererStub.withFault(fault);

                const wrapper = getWrapper({ is_first: false, is_last: false });

                await wrapper.find(`[data-test=${button_name}]`).trigger("click");

                const event = wrapper.emitted("moved-section-up-or-down-fault");
                if (!event) {
                    throw new Error("Expected a moved-section-up-or-down-fault event");
                }
                expect(event[0]).toStrictEqual([fault]);
            },
        );
    });
});
