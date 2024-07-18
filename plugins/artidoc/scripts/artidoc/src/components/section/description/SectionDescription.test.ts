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
import SectionDescription from "./SectionDescription.vue";
import SectionDescriptionSkeleton from "./SectionDescriptionSkeleton.vue";
import { InjectedSectionsStoreStub } from "@/helpers/stubs/InjectSectionsStoreStub";
import SectionDescriptionReadOnly from "./SectionDescriptionReadOnly.vue";
import { SECTIONS_STORE } from "@/stores/sections-store-injection-key";

const default_props = {
    editable_description: "Lorem ipsum",
    readonly_description: "Lorem ipsum",
    section_id: "1abc",
    is_edit_mode: false,
    upload_url: "/file/upload",
    add_attachment_to_waiting_list: vi.fn(),
    input_current_description: vi.fn(),
    is_image_upload_allowed: true,
};
describe("SectionDescription", () => {
    describe("while the sections are loading", () => {
        it("should display the skeleton", () => {
            const wrapper = shallowMount(SectionDescription, {
                global: {
                    provide: {
                        [SECTIONS_STORE.valueOf()]: InjectedSectionsStoreStub.withLoadingSections(
                            [],
                        ),
                    },
                },
                props: { ...default_props },
            });

            expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(false);
            expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(true);
            expect(wrapper.find("[data-test=editor]").exists()).toBe(false);
        });
    });

    describe("when the sections are loaded", () => {
        describe("when the editor mode is disabled", () => {
            it("should display the description", () => {
                const wrapper = shallowMount(SectionDescription, {
                    global: {
                        provide: {
                            [SECTIONS_STORE.valueOf()]:
                                InjectedSectionsStoreStub.withLoadedSections([]),
                        },
                    },
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
                    global: {
                        provide: {
                            [SECTIONS_STORE.valueOf()]:
                                InjectedSectionsStoreStub.withLoadedSections([]),
                        },
                    },
                    props: { ...default_props, is_edit_mode: true },
                });

                expect(wrapper.findComponent(SectionDescriptionReadOnly).exists()).toBe(false);
                expect(wrapper.findComponent(SectionDescriptionSkeleton).exists()).toBe(false);
                expect(wrapper.find("[data-test=editor]").exists()).toBe(true);
            });
        });
    });
});
