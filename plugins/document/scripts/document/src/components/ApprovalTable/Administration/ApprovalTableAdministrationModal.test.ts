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
import AdministrationModalReviewers from "./AdministrationModalReviewers.vue";
import { UserBuilder } from "../../../../tests/builders/UserBuilder";
import type { ApprovableDocument, ApprovalTable, Embedded, Item } from "../../../type";
import { TYPE_EMBEDDED } from "../../../constants";
import AdministrationModalMissingTable from "./AdministrationModalMissingTable.vue";
import { ApprovalTableReviewerBuilder } from "../../../../tests/builders/ApprovalTableReviewerBuilder";

describe("ApprovalTableAdministrationModal", () => {
    let trigger: HTMLButtonElement;

    function getWrapper(
        table: ApprovalTable | null = null,
        item: (Item & ApprovableDocument) | null = null,
    ): VueWrapper<InstanceType<typeof ApprovalTableAdministrationModal>> {
        return shallowMount(ApprovalTableAdministrationModal, {
            props: {
                trigger,
                table: table ?? new ApprovalTableBuilder(35).build(),
                item: item ?? new ItemBuilder(123).buildApprovableDocument(),
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
        await vi.waitUntil(
            () =>
                wrapper.find("[data-test=delete-confirmation-table-button]").attributes()
                    .disabled === undefined,
            { interval: 500, timeout: 2000 },
        );
        await wrapper.find("[data-test=delete-confirmation-table-button]").trigger("click");

        expect(deleteApprovalTable).toHaveBeenCalledWith(123);
        expect(wrapper.emitted("refresh-data")).not.toBe(undefined);
    });

    it("Should display error when delete api fails", async () => {
        const deleteApprovalTable = vi
            .spyOn(rest_querier, "deleteApprovalTable")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=delete-table-button]").trigger("click");
        await vi.waitUntil(
            () =>
                wrapper.find("[data-test=delete-confirmation-table-button]").attributes()
                    .disabled === undefined,
            { interval: 500, timeout: 2000 },
        );
        await wrapper.find("[data-test=delete-confirmation-table-button]").trigger("click");

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
        await wrapper
            .findComponent(AdministrationModalNotifications)
            .setValue(true, "table_do_reminder_value");
        await wrapper
            .findComponent(AdministrationModalNotifications)
            .setValue(2, "table_reminder_occurence_value");
        await wrapper
            .findComponent(AdministrationModalNotifications)
            .setValue("week", "table_reminder_occurence_unit_value");
        await wrapper
            .findComponent(AdministrationModalReviewers)
            .setValue([new ApprovalTableReviewerBuilder(101).build()], "table_reviewers_value");
        await wrapper
            .findComponent(AdministrationModalReviewers)
            .setValue(
                [new UserBuilder(102).build(), new UserBuilder(103).build()],
                "table_reviewers_to_add_value",
            );
        await wrapper
            .findComponent(AdministrationModalReviewers)
            .setValue(
                [{ id: "101_3", label: "Project members", short_name: "project_members" }],
                "table_reviewers_group_to_add_value",
            );

        await wrapper.find("[data-test=update-table-button]").trigger("click");

        expect(updateApprovalTable).toHaveBeenCalledWith(
            123,
            102,
            "enabled",
            "My comment",
            "all_at_once",
            [101],
            [102, 103],
            [3],
            14,
        );
        expect(wrapper.emitted("refresh-data")).not.toBe(undefined);
    });

    it("Should display error when update api fails", async () => {
        const updateApprovalTable = vi
            .spyOn(rest_querier, "updateApprovalTable")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=update-table-button]").trigger("click");

        expect(updateApprovalTable).toHaveBeenCalledWith(
            123,
            102,
            "disabled",
            "",
            "",
            [],
            [],
            [],
            0,
        );
        expect(wrapper.find("[data-test=admin-modal-error]").text()).toContain("Oh no!");
    });

    it("Should display new table form when table not linked to last item version", async () => {
        const putApprovalTable = vi
            .spyOn(rest_querier, "patchApprovalTable")
            .mockReturnValue(okAsync(null));
        const wrapper = getWrapper(new ApprovalTableBuilder(35).withVersionNumber(45).build(), {
            ...new ItemBuilder(123).withType(TYPE_EMBEDDED).buildApprovableDocument(),
            embedded_file_properties: { version_number: 15 },
        } as Embedded);

        expect(wrapper.findComponent(AdministrationModalMissingTable).exists()).toBe(true);

        await wrapper
            .findComponent(AdministrationModalMissingTable)
            .setValue("reset", "table_action_value");

        await wrapper.find("[data-test=update-table-button]").trigger("click");

        expect(putApprovalTable).toHaveBeenCalledWith(123, "reset");
    });
});
