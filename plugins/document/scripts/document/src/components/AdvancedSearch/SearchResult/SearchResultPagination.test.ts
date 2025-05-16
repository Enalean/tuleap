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

import type { VueWrapper } from "@vue/test-utils";
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import SearchResultPagination from "./SearchResultPagination.vue";
import type { Dictionary } from "vue-router/types/router";
import * as router from "vue-router";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

jest.mock("vue-router");

describe("SearchResultPagination", () => {
    const total = 172;
    const limit = 50;
    let query: Dictionary<string>;

    beforeEach(() => {
        query = {
            q: "Lorem ipsum",
        };
        jest.spyOn(router, "useRoute").mockReturnValue({
            params: {},
            query,
        } as unknown as RouteLocationNormalizedLoaded);
    });

    function getPagination(
        from: number,
        to: number,
    ): VueWrapper<InstanceType<typeof SearchResultPagination>> {
        if (from !== 0) {
            query.offset = String(from);
        }
        return shallowMount(SearchResultPagination, {
            props: {
                from,
                to,
                total,
                limit,
            },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
                directives: {
                    "dompurify-html": jest.fn(),
                },
            },
        });
    }

    it("should display pages", () => {
        const wrapper = getPagination(0, 49);

        expect(wrapper.vm.pages).toBe(
            '<span class="tlp-pagination-number">1</span> – <span class="tlp-pagination-number">50</span> of <span class="tlp-pagination-number">172</span>',
        );
    });

    describe("disabled buttons", () => {
        it("should disable begin/previous buttons if first index is displayed", () => {
            const wrapper = getPagination(0, 49);

            expect(wrapper.find("[data-test=begin-disabled]").exists()).toBe(true);
            expect(wrapper.find("[data-test=previous-disabled]").exists()).toBe(true);
            expect(wrapper.find("[data-test=begin]").exists()).toBe(false);
            expect(wrapper.find("[data-test=previous]").exists()).toBe(false);
        });

        it("should enable begin/previous buttons if first index is not displayed", () => {
            const wrapper = getPagination(1, 49);

            expect(wrapper.find("[data-test=begin-disabled]").exists()).toBe(false);
            expect(wrapper.find("[data-test=previous-disabled]").exists()).toBe(false);
            expect(wrapper.find("[data-test=begin]").exists()).toBe(true);
            expect(wrapper.find("[data-test=previous]").exists()).toBe(true);
        });

        it("should disable next/end buttons if last index is displayed", () => {
            const wrapper = getPagination(149, 171);

            expect(wrapper.find("[data-test=next-disabled]").exists()).toBe(true);
            expect(wrapper.find("[data-test=end-disabled]").exists()).toBe(true);
            expect(wrapper.find("[data-test=next]").exists()).toBe(false);
            expect(wrapper.find("[data-test=end]").exists()).toBe(false);
        });

        it("should enable next/end buttons if last index is not displayed", () => {
            const wrapper = getPagination(149, 170);

            expect(wrapper.find("[data-test=next-disabled]").exists()).toBe(false);
            expect(wrapper.find("[data-test=end-disabled]").exists()).toBe(false);
            expect(wrapper.find("[data-test=next]").exists()).toBe(true);
            expect(wrapper.find("[data-test=end]").exists()).toBe(true);
        });
    });

    describe("new routes", () => {
        it.each([[1], [50], [51], [100]])(
            "should remove the offset=0 from the `begin` route to clean the url",
            (from: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.vm.begin_to).toStrictEqual({
                    params: {},
                    query: { q: "Lorem ipsum" },
                });
            },
        );

        it.each([[1], [49], [50]])(
            "should remove the offset=0 from the `previous` route to clean the url",
            (from: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.vm.to_previous).toStrictEqual({
                    params: {},
                    query: { q: "Lorem ipsum" },
                });
            },
        );

        it.each([
            [51, 1],
            [100, 50],
        ])(
            "when first displayed index is %i then new offset for `previous` will be %i",
            (from: number, expected_offset: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.vm.to_previous).toStrictEqual({
                    params: {},
                    query: {
                        q: "Lorem ipsum",
                        offset: String(expected_offset),
                    },
                });
            },
        );

        it.each([
            [50, 100],
            [100, 150],
            [121, 171],
        ])(
            "should adjust the offset to be able to retrieve next results",
            (from: number, expected_offset: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.vm.to_next).toStrictEqual({
                    params: {},
                    query: {
                        q: "Lorem ipsum",
                        offset: String(expected_offset),
                    },
                });
            },
        );
    });
});
