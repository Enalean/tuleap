/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { FileProperties, ItemSearchResult } from "../../../../type";
import { RouterLinkStub, shallowMount, mount } from "@vue/test-utils";
import CellTitle from "./CellTitle.vue";
import type { ConfigurationState } from "../../../../store/configuration";
import { getGlobalTestOptions } from "../../../../helpers/global-options-for-test";
import type { Dropdown } from "@tuleap/tlp-dropdown";
import * as tlp_dropdown from "@tuleap/tlp-dropdown";
import * as strict_inject from "@tuleap/vue-strict-inject";

jest.mock("@tuleap/tlp-dropdown");

describe("CellTitle", () => {
    it("should output a link for File", () => {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue({});
        const wrapper = shallowMount(CellTitle, {
            props: {
                item: {
                    id: 123,
                    type: "file",
                    title: "Lorem",
                    file_properties: {
                        file_type: "text/html",
                        download_href: "/path/to/file",
                        open_href: null,
                    } as FileProperties,
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: "101",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/path/to/file");
        expect(link.attributes().title).toBe("Lorem");
    });

    it("should output a link to open a File", () => {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue({});
        const wrapper = shallowMount(CellTitle, {
            props: {
                item: {
                    id: 123,
                    type: "file",
                    title: "Lorem",
                    file_properties: {
                        file_type: "text/html",
                        download_href: "/path/to/file",
                        open_href: "/path/to/open/file",
                    } as FileProperties,
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: "101",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/path/to/open/file");
        expect(link.attributes().title).toBe("Lorem");
    });

    it("should output a link for Wiki", () => {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue({});
        const wrapper = shallowMount(CellTitle, {
            props: {
                item: {
                    id: 123,
                    type: "wiki",
                    title: "Lorem",
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: "101",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        const link = wrapper.find("[data-test=link]");
        expect(link.attributes().href).toBe("/plugins/docman/?group_id=101&action=show&id=123");
        expect(link.attributes().title).toBe("Lorem");
    });

    it("should set the empty icon for empty document", () => {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue({
            other: { icon: "other-icon" },
        });
        const wrapper = shallowMount(CellTitle, {
            props: {
                item: {
                    id: 123,
                    type: "empty",
                    title: "Lorem",
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: "101",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        expect(wrapper.find("[data-test=icon]").classes()).toContain("document-empty-icon");
    });

    it("should set the empty icon for other type document", () => {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue({
            other: { icon: "other-icon" },
        });
        const wrapper = shallowMount(CellTitle, {
            props: {
                item: {
                    id: 123,
                    type: "other",
                    title: "Lorem",
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: "101",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        expect(wrapper.find("[data-test=icon]").classes()).not.toContain("document-empty-icon");
        expect(wrapper.find("[data-test=icon]").classes()).toContain("other-icon");
    });

    it("should output a route link for Embedded", () => {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue({});
        const fake_dropdown_object = {
            addEventListener: jest.fn(),
            removeEventListener: jest.fn(),
        } as unknown as Dropdown;

        jest.spyOn(tlp_dropdown, "createDropdown").mockReturnValue(fake_dropdown_object);

        const wrapper = mount(CellTitle, {
            props: {
                item: {
                    id: 123,
                    type: "embedded",
                    title: "Lorem",
                    parents: [
                        {
                            id: 120,
                            title: "Path",
                        },
                        {
                            id: 121,
                            title: "To",
                        },
                        {
                            id: 122,
                            title: "Folder",
                        },
                    ],
                } as unknown as ItemSearchResult,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        configuration: {
                            state: {
                                project_id: "101",
                            } as unknown as ConfigurationState,
                            namespaced: true,
                        },
                    },
                }),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });

        expect(wrapper.vm.in_app_link).toStrictEqual({
            name: "item",
            params: {
                folder_id: "120",
                item_id: "123",
            },
        });
        const link = wrapper.find("[data-test=router-link]");
        expect(link.attributes().title).toBe("Lorem");
    });
});
