/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

import { shallowMount } from "@vue/test-utils";
import FieldFromJira from "./FieldFromJira.vue";
import {
    Credentials,
    JiraImportData,
    ProjectList,
    State,
    TrackerList,
} from "../../../../store/type";
import { createStoreMock } from "../../../../../../../../../src/scripts/vue-components/store-wrapper-jest";

describe("FieldFromJira", () => {
    it("Displays a card for bug tracker", () => {
        const wrapper = shallowMount(FieldFromJira, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        from_jira_data: {
                            credentials: {
                                server_url: "https://example.com",
                                user_email: "user-email@example.com",
                                token: "azerty1234",
                            } as Credentials,
                            project: {
                                id: "AB",
                                label: "A beautifull porject",
                            } as ProjectList,
                            tracker: {
                                id: "bug",
                                name: "Bugs",
                            } as TrackerList,
                        } as JiraImportData,
                    } as State,
                    getters: {
                        is_created_from_jira: true,
                    },
                }),
            },
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("Displays nothing if not created from jira", () => {
        const wrapper = shallowMount(FieldFromJira, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        from_jira_data: {} as JiraImportData,
                    } as State,
                    getters: {
                        is_created_from_jira: false,
                    },
                }),
            },
        });

        expect(wrapper.find("[data-test=jira-server]").exists()).toBe(false);
    });
});
