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
import ApprovalTableAdministrationModal from "./ApprovalTableAdministrationModal.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { PROJECT, USER_LOCALE } from "../../../configuration-keys";
import { ProjectBuilder } from "../../../../tests/builders/ProjectBuilder";
import { ApprovalTableBuilder } from "../../../../tests/builders/ApprovalTableBuilder";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import * as rest_querier from "../../../api/approval-table-rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import AdministrationModalGlobalSettings from "./AdministrationModalGlobalSettings.vue";
import AdministrationModalNotifications from "./AdministrationModalNotifications.vue";

describe("ApprovalTableAdministrationModal", () => {
    let trigger: HTMLButtonElement;

    function getWrapper(): VueWrapper<InstanceType<typeof ApprovalTableAdministrationModal>> {
        return shallowMount(ApprovalTableAdministrationModal, {
            props: {
                trigger,
                table: new ApprovalTableBuilder(35).build(),
                item: new ItemBuilder(123).build(),
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [USER_LOCALE.valueOf()]: "fr_FR",
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
            },
        });
    }

    beforeEach(() => {
        const doc = document.implementation.createHTMLDocument();
        trigger = doc.createElement("button");
    });

    it("Should call delete api when clicking on Delete button", async () => {
        const deleteApprovalTable = vi
            .spyOn(rest_querier, "deleteApprovalTable")
            .mockReturnValue(okAsync(null));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=delete-table-button]").trigger("click");

        expect(deleteApprovalTable).toHaveBeenCalledWith(123);
        expect(wrapper.emitted("refresh-data")).not.toBe(undefined);
    });

    it("Should display error when delete api fails", async () => {
        const deleteApprovalTable = vi
            .spyOn(rest_querier, "deleteApprovalTable")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=delete-table-button]").trigger("click");

        expect(deleteApprovalTable).toHaveBeenCalledWith(123);
        expect(wrapper.find("[data-test=admin-modal-error]").text()).toContain("Oh no!");
    });

    it("Should call update api when clicking on update button", async () => {
        const updateApprovalTable = vi
            .spyOn(rest_querier, "updateApprovalTable")
            .mockReturnValue(okAsync(null));
        const wrapper = getWrapper();

        await wrapper
            .findComponent(AdministrationModalGlobalSettings)
            .setValue("enabled", "table_status_value");
        await wrapper
            .findComponent(AdministrationModalGlobalSettings)
            .setValue("My comment", "table_comment_value");
        await wrapper
            .findComponent(AdministrationModalNotifications)
            .setValue("all_at_once", "table_notification_value");

        await wrapper.find("[data-test=update-table-button]").trigger("click");

        expect(updateApprovalTable).toHaveBeenCalledWith(
            123,
            102,
            "enabled",
            "My comment",
            "all_at_once",
        );
        expect(wrapper.emitted("refresh-data")).not.toBe(undefined);
    });

    it("Should display error when update api fails", async () => {
        const updateApprovalTable = vi
            .spyOn(rest_querier, "updateApprovalTable")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=update-table-button]").trigger("click");

        expect(updateApprovalTable).toHaveBeenCalledWith(123, 102, "disabled", "", "");
        expect(wrapper.find("[data-test=admin-modal-error]").text()).toContain("Oh no!");
    });
});
