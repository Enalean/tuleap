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

import type { BacklogItem, TestDefinition } from "../../type";
import BacklogItemCoverage from "./BacklogItemCoverage.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("BacklogItemCoverage", () => {
    function createWrapper(
        backlog_item: BacklogItem,
    ): VueWrapper<InstanceType<typeof BacklogItemCoverage>> {
        return shallowMount(BacklogItemCoverage, {
            props: {
                backlog_item,
            },
            global: {
                ...getGlobalTestOptions({}),
            },
        });
    }

    it("Display a skeleton when the tests are being loaded", async () => {
        const wrapper = await createWrapper({ is_loading_test_definitions: true } as BacklogItem);

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Does not display anything if there is no tests", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
        } as BacklogItem);

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Does not display anything if there is only tests not planned in any campaigns", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [
                { test_status: null },
                { test_status: null },
                { test_status: null },
            ] as TestDefinition[],
        } as BacklogItem);

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Displays the number of tests", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [
                { test_status: "passed" },
                { test_status: "notrun" },
                { test_status: null },
            ] as TestDefinition[],
        } as BacklogItem);

        expect(wrapper.find("[data-test=nb-tests]").text()).toBe("2 planned tests");
    });

    it("Marks the backlog item as failed if there is at least one failed", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [
                { test_status: null },
                { test_status: "passed" },
                { test_status: "blocked" },
                { test_status: "notrun" },
                { test_status: "failed" },
            ] as TestDefinition[],
        } as BacklogItem);

        expect(
            wrapper
                .find("[data-test=backlog-item-icon]")
                .classes("test-plan-backlog-item-coverage-icon-failed"),
        ).toBe(true);
    });

    it("Marks the backlog item as blocked if there is no failed and at least one blocked", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [
                { test_status: null },
                { test_status: "passed" },
                { test_status: "blocked" },
                { test_status: "notrun" },
                { test_status: "passed" },
            ] as TestDefinition[],
        } as BacklogItem);

        expect(
            wrapper
                .find("[data-test=backlog-item-icon]")
                .classes("test-plan-backlog-item-coverage-icon-blocked"),
        ).toBe(true);
    });

    it("Marks the backlog item as notrun if there is no failed, no blocked and at least one notrun", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [
                { test_status: null },
                { test_status: "passed" },
                { test_status: "passed" },
                { test_status: "notrun" },
                { test_status: "passed" },
            ] as TestDefinition[],
        } as BacklogItem);

        expect(
            wrapper
                .find("[data-test=backlog-item-icon]")
                .classes("test-plan-backlog-item-coverage-icon-notrun"),
        ).toBe(true);
    });

    it("Marks the backlog item as passed if all tests are passed", async () => {
        const wrapper = await createWrapper({
            is_loading_test_definitions: false,
            test_definitions: [
                { test_status: "passed" },
                { test_status: "passed" },
                { test_status: "passed" },
                { test_status: "passed" },
                { test_status: "passed" },
            ] as TestDefinition[],
        } as BacklogItem);

        expect(
            wrapper
                .find("[data-test=backlog-item-icon]")
                .classes("test-plan-backlog-item-coverage-icon-passed"),
        ).toBe(true);
    });
});
