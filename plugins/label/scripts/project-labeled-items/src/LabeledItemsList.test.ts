/*
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
import type { MockInstance } from "vitest";
import { describe, it, beforeEach, expect, vi } from "vitest";
import { shallowMount, type VueWrapper } from "@vue/test-utils";
import * as rest_querier from "./rest-querier";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import LabeledItemsList from "./LabeledItemsList.vue";
import LabeledItem from "./LabeledItem.vue";
import { getGlobalTestOptions } from "./helpers/global-options-for-tests";

vi.useFakeTimers();

describe("LabeledItemsList", () => {
    let getLabeledItems: MockInstance;

    const getWrapper = async (labels_ids: number[]): Promise<VueWrapper> => {
        const wrapper = shallowMount(LabeledItemsList, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                labels_id: JSON.stringify(labels_ids),
                project_id: "101",
            },
        });

        await vi.runOnlyPendingTimersAsync();

        return wrapper;
    };

    beforeEach(() => {
        getLabeledItems = vi.spyOn(rest_querier, "getLabeledItems");
    });

    it("Should display an error when no labels id are provided", async () => {
        const wrapper = await getWrapper([]);

        expect(wrapper.find("[data-test=widget-error]").exists()).toBe(true);
    });

    it("Should display an error when REST route fails", async () => {
        mockFetchError(getLabeledItems, {
            error_json: {
                error: {
                    code: 404,
                    message: "Not Found",
                },
            },
        });

        const wrapper = await getWrapper([1]);

        expect(wrapper.find("[data-test=widget-error]").exists()).toBe(true);
    });

    it("Should display an empty state when no items are found", async () => {
        getLabeledItems.mockReturnValue(Promise.resolve({ labeled_items: [] }));

        const wrapper = await getWrapper([1]);

        expect(wrapper.find("[data-test=items-list-empty-state]").exists()).toBe(true);
    });

    describe("Labeled items display", () => {
        const labels_ids = [3, 4];

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
                }),
            );

            const wrapper = await getWrapper(labels_ids);

            expect(wrapper.findAllComponents(LabeledItem)).toHaveLength(2);
        });

        it.each([
            [true, "will be displayed"],
            [false, "won't be displayed"],
        ])(
            "When has_more in the payload is %s, then the load-more button %s",
            async (has_more_items_to_load: boolean) => {
                getLabeledItems.mockReturnValue(
                    Promise.resolve({
                        labeled_items: [{ title: "test 1" }],
                        has_more: has_more_items_to_load,
                    }),
                );

                const wrapper = await getWrapper(labels_ids);

                expect(wrapper.find("[data-test=load-more-section]").exists()).toBe(
                    has_more_items_to_load,
                );
            },
        );

        it("Loads the next page of items", async () => {
            getLabeledItems
                .mockReturnValueOnce(
                    Promise.resolve({
                        labeled_items: [{ title: "test 1" }],
                        offset: 0,
                        has_more: true,
                    }),
                )
                .mockReturnValueOnce(
                    Promise.resolve({
                        labeled_items: [{ title: "test 2" }],
                        offset: 50,
                        has_more: false,
                    }),
                );

            const wrapper = await getWrapper(labels_ids);

            expect(getLabeledItems.mock.calls).toHaveLength(1);
            expect(getLabeledItems.mock.calls[0]).toStrictEqual(["101", labels_ids, 0, 50]);

            wrapper.find("[data-test=load-more-button]").trigger("click");

            expect(getLabeledItems.mock.calls).toHaveLength(2);
            expect(getLabeledItems.mock.calls[1]).toStrictEqual(["101", labels_ids, 50, 50]);
        });
    });
});
