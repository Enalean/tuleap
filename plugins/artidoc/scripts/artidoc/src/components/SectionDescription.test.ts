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
import { shallowMount } from "@vue/test-utils";
import SectionDescription from "@/components/SectionDescription.vue";
import SectionDescriptionSkeleton from "@/components/SectionDescriptionSkeleton.vue";
import * as sectionsStore from "@/stores/useSectionsStore";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import SectionDescriptionReadOnly from "@/components/description/SectionDescriptionReadOnly.vue";

const default_props = {
    editable_description: "Lorem ipsum",
    readonly_description: "Lorem ipsum",
    artifact_id: 1,
    is_edit_mode: false,
    input_current_description: vi.fn(),
};
describe("SectionDescription", () => {
    describe("while the sections are loading", () => {
        beforeEach(() => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadingSections(),
            );
        });
        it("should display the skeleton", () => {
            const wrapper = shallowMount(SectionDescription, {
                props: { ...default_props },
            });

            expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(false);
            expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(true);
            expect(wrapper.find("[data-test=editor]").exists()).toBe(false);
        });
    });

    describe("when the sections are loaded", () => {
        beforeEach(() => {
            vi.spyOn(sectionsStore, "useInjectSectionsStore").mockReturnValue(
                InjectedSectionsStoreStub.withLoadedSections([]),
            );
        });
        describe("when the editor mode is disabled", () => {
            it("should display the description", () => {
                const wrapper = shallowMount(SectionDescription, {
                    props: default_props,
                });
                expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(true);
                expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(false);
                expect(wrapper.find("[data-test=editor]").exists()).toBe(false);
            });
        });
        describe("when the editor mode is enabled", () => {
            it("should display the editor", () => {
                const wrapper = shallowMount(SectionDescription, {
                    props: { ...default_props, is_edit_mode: true },
                });

                expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(false);
                expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(false);
                expect(wrapper.find("[data-test=editor]").exists()).toBe(true);
            });
        });
    });
});
