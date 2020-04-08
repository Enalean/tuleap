/*
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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
import Vue from "vue";
import * as rest_querier from "./rest-querier.js";
import LabeledItemsList from "./LabeledItemsList.vue";
import { mockFetchError } from "../../../../../src/themes/tlp/mocks/tlp-fetch-mock-helper.js";
import GetTextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";

describe("LabeledItemsList", () => {
    let getLabeledItems;
    let LabeledItemsListVueElement;

    beforeEach(() => {
        getLabeledItems = jest.spyOn(rest_querier, "getLabeledItems");

        Vue.use(GetTextPlugin, {
            translations: {},
            silent: true,
        });
        Vue.use(VueDOMPurifyHTML);
        LabeledItemsListVueElement = Vue.extend(LabeledItemsList);
    });

    it("Should display an error when no labels id are provided", () => {
        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[]",
                projectId: "101",
            },
        });

        vm.$mount();

        expect(vm.error).toBe(true);
    });

    it("Should display an error when REST route fails", async () => {
        const error_json = {
            error: {
                code: 404,
                message: "Not Found",
            },
        };
        mockFetchError(getLabeledItems, { error_json });

        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[1]",
                projectId: "101",
            },
        });

        vm.$mount();

        await Vue.nextTick();
        await Vue.nextTick();
        expect(vm.error).toEqual("404 Not Found");
    });

    it("Should display an empty state when no items are found", async () => {
        getLabeledItems.mockReturnValue(Promise.resolve({ labeled_items: [] }));

        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[1]",
                projectId: "101",
            },
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.items).toEqual([]);
    });

    it("Should display a list of items.", async () => {
        getLabeledItems.mockReturnValue(
            Promise.resolve({
                labeled_items: [
                    {
                        title: "test 1",
                    },
                    {
                        title: "test 2",
                    },
                ],
            })
        );

        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[3, 4]",
                projectId: "101",
            },
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.items).toEqual([{ title: "test 1" }, { title: "test 2" }]);
    });

    it("Displays a [load more] button, if there is more items to display", async () => {
        getLabeledItems.mockReturnValue(
            Promise.resolve({
                labeled_items: [{ title: "test 1" }],
                has_more: true,
            })
        );

        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[3, 4]",
                projectId: "101",
            },
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.has_more_items).toEqual(true);
    });

    it("Does not display a [load more] button, if there is not more items to display", async () => {
        getLabeledItems.mockReturnValue(
            Promise.resolve({
                labeled_items: [{ title: "test 1" }],
                has_more: false,
            })
        );

        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[3, 4]",
                projectId: "101",
            },
        });

        vm.$mount();

        await Vue.nextTick();
        expect(vm.has_more_items).toEqual(false);
    });

    it("Loads the next page of items", async () => {
        getLabeledItems
            .mockReturnValueOnce(
                Promise.resolve({
                    labeled_items: [{ title: "test 1" }],
                    offset: 0,
                    has_more: true,
                })
            )
            .mockReturnValueOnce(
                Promise.resolve({
                    labeled_items: [{ title: "test 2" }],
                    offset: 50,
                    has_more: false,
                })
            );

        const vm = new LabeledItemsListVueElement({
            propsData: {
                labelsId: "[3, 4]",
                projectId: "101",
            },
        });

        vm.$mount();

        await Vue.nextTick();
        expect(getLabeledItems.mock.calls.length).toEqual(1);
        expect(getLabeledItems.mock.calls[0]).toEqual(["101", [3, 4], 0, 50]);

        vm.loadMore();
        expect(getLabeledItems.mock.calls.length).toEqual(2);
        expect(getLabeledItems.mock.calls[1]).toEqual(["101", [3, 4], 50, 50]);
    });
});
