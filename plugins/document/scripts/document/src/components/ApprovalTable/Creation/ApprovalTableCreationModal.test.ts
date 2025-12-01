/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import ApprovalTableCreationModal from "./ApprovalTableCreationModal.vue";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { PROJECT, USER_LOCALE } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";
import * as ugroups from "../../../helpers/permissions/ugroups";
import { errAsync, okAsync } from "neverthrow";
import * as rest_querier from "../../../api/approval-table-rest-querier";
import { Fault } from "@tuleap/fault";

vi.useFakeTimers();

const noop = (): void => {};
class ResizeObserverMock {
    disconnect = noop;
    observe = noop;
    unobserve = noop;
}
vi.stubGlobal("ResizeObserver", ResizeObserverMock);

describe("ApprovalTableCreationModal", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof ApprovalTableCreationModal>> {
        return shallowMount(ApprovalTableCreationModal, {
            props: { item: new ItemBuilder(123).build() },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [USER_LOCALE.valueOf()]: "en_US",
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    beforeEach(() => {
        vi.spyOn(ugroups, "loadProjectUserGroups").mockReturnValue(
            okAsync([
                { id: "101_3", label: "Project Members", short_name: "project_members" },
                { id: "154", label: "My_Group", short_name: "my_group" },
            ]),
        );
    });

    it("Should display error when failed to created", async () => {
        const postTable = vi
            .spyOn(rest_querier, "postApprovalTable")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        await wrapper.find("[data-test=creation-button]").trigger("click");
        await wrapper.find("[data-test=create-table-button]").trigger("click");
        await vi.runOnlyPendingTimersAsync();

        expect(postTable).toHaveBeenCalledWith(123, [], []);
        expect(wrapper.find("[data-test=creation-error-message]").text()).toContain("Oh no!");
    });

    it("Should close modal and emit event when succeed to create", async () => {
        const postTable = vi
            .spyOn(rest_querier, "postApprovalTable")
            .mockReturnValue(okAsync(null));
        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        await wrapper.find("[data-test=creation-button]").trigger("click");
        await wrapper.find("[data-test=create-table-button]").trigger("click");
        await vi.runOnlyPendingTimersAsync();

        expect(postTable).toHaveBeenCalledWith(123, [], []);
        expect(wrapper.emitted("table-created")).not.toBe(undefined);
    });
});
