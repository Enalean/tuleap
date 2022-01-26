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

import type { Wrapper } from "@vue/test-utils";
import { createLocalVue, RouterLinkStub, shallowMount } from "@vue/test-utils";
import SearchResultPagination from "./SearchResultPagination.vue";
import VueRouter from "vue-router";
import GettextPlugin from "vue-gettext";
import VueDOMPurifyHTML from "vue-dompurify-html";
import type { Dictionary } from "vue-router/types/router";

describe("SearchResultPagination", () => {
    const total = 172;
    const limit = 50;

    function getPagination(from: number, to: number): Wrapper<SearchResultPagination> {
        // We don't use localVue from helpers since the inclusion of VueRouter via localVue.use()
        // prevents us to properly test and mock stuff here.
        const localVue = createLocalVue();
        localVue.use(VueDOMPurifyHTML);
        localVue.use(GettextPlugin, {
            translations: {},
            silent: true,
        });

        const query: Dictionary<string> = {
            q: "Lorem ipsum",
        };
        if (from !== 0) {
            query.offset = String(from);
        }

        return shallowMount(SearchResultPagination, {
            localVue,
            propsData: {
                from,
                to,
                total,
                limit,
            },
            mocks: {
                $route: {
                    params: {
                        folder_id: 101,
                    },
                    query,
                },
                $router: new VueRouter(),
            },
            stubs: {
                RouterLink: RouterLinkStub,
            },
        });
    }

    it("should display pages in human readable numbers", () => {
        const wrapper = getPagination(0, 49);

        expect(wrapper.find("[data-test=pages]").text()).toBe("1 – 50 of 172");
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

                expect(wrapper.find("[data-test=begin]").props().to.query).toStrictEqual({
                    q: "Lorem ipsum",
                });
            }
        );

        it.each([[1], [49], [50]])(
            "should remove the offset=0 from the `previous` route to clean the url",
            (from: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.find("[data-test=previous]").props().to.query).toStrictEqual({
                    q: "Lorem ipsum",
                });
            }
        );

        it.each([
            [51, 1],
            [100, 50],
        ])(
            "when first displayed index is %i then new offset for `previous` will be %i",
            (from: number, expected_offset: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.find("[data-test=previous]").props().to.query).toStrictEqual({
                    q: "Lorem ipsum",
                    offset: String(expected_offset),
                });
            }
        );

        it.each([
            [50, 100],
            [100, 150],
            [121, 171],
        ])(
            "should adjust the offset to be able to retrieve next results",
            (from: number, expected_offset: number) => {
                const wrapper = getPagination(from, from + limit - 1);

                expect(wrapper.find("[data-test=next]").props().to.query).toStrictEqual({
                    q: "Lorem ipsum",
                    offset: String(expected_offset),
                });
            }
        );
    });
});
