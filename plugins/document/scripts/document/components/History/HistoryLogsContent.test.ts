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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue";
import HistoryLogsContent from "./HistoryLogsContent.vue";
import type { LogEntry } from "../../api/log-rest-querier";
import type { RestUser } from "../../api/rest-querier";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { ConfigurationState } from "../../store/configuration";

describe("HistoryLogsContent", () => {
    it("should display old and new value", () => {
        const wrapper = shallowMount(HistoryLogsContent, {
            localVue,
            propsData: {
                log_entries: [
                    {
                        when: "2021-10-06",
                        who: { id: 102 } as unknown as RestUser,
                        what: "WAT",
                        old_value: "old",
                        new_value: "new",
                        diff_link: null,
                    } as LogEntry,
                ],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should collapse cols when there is no old value", () => {
        const wrapper = shallowMount(HistoryLogsContent, {
            localVue,
            propsData: {
                log_entries: [
                    {
                        when: "2021-10-06",
                        who: { id: 102 } as unknown as RestUser,
                        what: "WAT",
                        old_value: null,
                        new_value: "new",
                        diff_link: null,
                    } as LogEntry,
                ],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should collapse cols when there is no new value", () => {
        const wrapper = shallowMount(HistoryLogsContent, {
            localVue,
            propsData: {
                log_entries: [
                    {
                        when: "2021-10-06",
                        who: { id: 102 } as unknown as RestUser,
                        what: "WAT",
                        old_value: "old",
                        new_value: null,
                        diff_link: null,
                    } as LogEntry,
                ],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should collapse cols when there is no new value nor old value", () => {
        const wrapper = shallowMount(HistoryLogsContent, {
            localVue,
            propsData: {
                log_entries: [
                    {
                        when: "2021-10-06",
                        who: { id: 102 } as unknown as RestUser,
                        what: "WAT",
                        old_value: null,
                        new_value: null,
                        diff_link: null,
                    } as LogEntry,
                ],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("should display a link to a diff", () => {
        const wrapper = shallowMount(HistoryLogsContent, {
            localVue,
            propsData: {
                log_entries: [
                    {
                        when: "2021-10-06",
                        who: { id: 102 } as unknown as RestUser,
                        what: "WAT",
                        old_value: null,
                        new_value: null,
                        diff_link: "/path/to/diff",
                    } as LogEntry,
                ],
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            date_time_format: "Y-m-d H:i",
                            relative_dates_display: "relative_first-absolute_shown",
                            user_locale: "en_US",
                        } as unknown as ConfigurationState,
                    },
                }),
            },
            stubs: {
                "tlp-relative-date": true,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });
});
