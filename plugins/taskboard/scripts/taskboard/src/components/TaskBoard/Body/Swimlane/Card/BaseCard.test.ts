/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import BaseCard from "./BaseCard.vue";
import { createStoreMock } from "../../../../../../../../../../src/www/scripts/vue-components/store-wrapper-jest";
import { Card, User } from "../../../../../type";

jest.useFakeTimers();

describe("BaseCard", () => {
    it("doesn't add a dummy taskboard-card-background- class if the card has no background color", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: false } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "",
                    assignees: [] as User[]
                } as Card
            }
        });
        expect(wrapper.classes()).not.toContain("taskboard-card-background-");
    });

    it("adds accessibility class if user needs it and card has a background color", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: true } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    assignees: [] as User[]
                } as Card
            }
        });
        expect(wrapper.contains(".taskboard-card-accessibility")).toBe(true);
        expect(wrapper.classes()).toContain("taskboard-card-with-accessibility");
    });

    it("does not add accessibility class if user needs it but card has no background color", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: true } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "",
                    assignees: [] as User[]
                } as Card
            }
        });
        expect(wrapper.contains(".taskboard-card-accessibility")).toBe(false);
        expect(wrapper.classes()).not.toContain("taskboard-card-with-accessibility");
    });

    it("adds the show classes", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: false } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    assignees: [] as User[]
                } as Card
            }
        });
        expect(wrapper.classes()).toContain("taskboard-card-show");
    });

    it("does not add the show classes if card has been dropped", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: false } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    assignees: [] as User[],
                    has_been_dropped: true
                } as Card
            }
        });
        expect(wrapper.classes()).not.toContain("taskboard-card-show");
    });

    it("removes the show classes after 500ms", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: false } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    assignees: [] as User[]
                } as Card
            }
        });
        jest.runAllTimers();
        expect(setTimeout).toHaveBeenCalledWith(expect.any(Function), 500);
        expect(wrapper.classes()).not.toContain("taskboard-card-show");
    });

    it("includes the remaining effort slot", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: false } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    assignees: [] as User[]
                } as Card
            },
            slots: {
                remaining_effort: '<div class="my-remaining-effort"></div>'
            }
        });

        expect(wrapper.contains(".taskboard-card > .my-remaining-effort")).toBe(true);
    });

    it("includes the initial effort slot", () => {
        const wrapper = shallowMount(BaseCard, {
            mocks: {
                $store: createStoreMock({ state: { user: { user_has_accessibility_mode: false } } })
            },
            propsData: {
                card: {
                    id: 43,
                    color: "lake-placid-blue",
                    background_color: "fiesta-red",
                    assignees: [] as User[]
                } as Card
            },
            slots: {
                initial_effort: '<div class="my-initial-effort"></div>'
            }
        });

        expect(
            wrapper.contains(".taskboard-card-content > .taskboard-card-info > .my-initial-effort")
        ).toBe(true);
    });
});
