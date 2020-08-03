/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import BacklogItemCard from "./BacklogItemCard.vue";
import { BacklogItem } from "../../type";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../store/type";

describe("BacklogItemCard", () => {
    it("Displays the backlog item as a card", () => {
        const wrapper = shallowMount(BacklogItemCard, {
            propsData: {
                backlog_item: {
                    id: 123,
                    label: "A backlog item",
                    color: "fiesta-red",
                    short_type: "bug",
                    is_expanded: false,
                    artifact: {
                        id: 42,
                    },
                } as BacklogItem,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        milestone_id: 11,
                    } as RootState,
                }),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Expands the item when the user click on it", async () => {
        const $store = createStoreMock({
            state: {
                backlog_item: {},
            } as RootState,
        });

        const backlog_item = {
            id: 123,
            label: "A backlog item",
            color: "fiesta-red",
            short_type: "bug",
            is_expanded: false,
            artifact: {
                id: 42,
            },
        } as BacklogItem;

        const wrapper = shallowMount(BacklogItemCard, {
            propsData: {
                backlog_item,
            },
            mocks: {
                $store,
            },
        });

        await wrapper.trigger("click");

        expect($store.commit).toHaveBeenCalledWith("backlog_item/expandBacklogItem", backlog_item);
    });

    it("Collapses the item when the user reclick on it", async () => {
        const $store = createStoreMock({
            state: {
                backlog_item: {},
            } as RootState,
        });

        const backlog_item = {
            id: 123,
            label: "A backlog item",
            color: "fiesta-red",
            short_type: "bug",
            is_expanded: true,
            artifact: {
                id: 42,
            },
        } as BacklogItem;

        const wrapper = shallowMount(BacklogItemCard, {
            propsData: {
                backlog_item,
            },
            mocks: {
                $store,
            },
        });

        await wrapper.trigger("click");

        expect($store.commit).toHaveBeenCalledWith(
            "backlog_item/collapseBacklogItem",
            backlog_item
        );
    });

    it("Marks a backlog item as just refreshed", () => {
        jest.useFakeTimers();

        const $store = createStoreMock({
            state: {
                backlog_item: {},
            } as RootState,
        });

        const backlog_item = {
            id: 123,
            is_just_refreshed: true,
        } as BacklogItem;

        const wrapper = shallowMount(BacklogItemCard, {
            propsData: {
                backlog_item,
            },
            mocks: {
                $store,
            },
        });

        expect(wrapper.classes("test-plan-backlog-item-is-just-refreshed")).toBe(true);

        jest.advanceTimersByTime(1000);

        expect($store.commit).toHaveBeenCalledWith(
            "backlog_item/removeIsJustRefreshedFlagOnBacklogItem",
            backlog_item
        );
    });
});
