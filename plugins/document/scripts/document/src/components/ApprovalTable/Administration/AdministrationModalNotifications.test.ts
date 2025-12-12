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

import { describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import AdministrationModalNotifications from "./AdministrationModalNotifications.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import { ApprovalTableBuilder } from "../../../../tests/builders/ApprovalTableBuilder";
import * as rest_querier from "../../../api/approval-table-rest-querier";
import { okAsync } from "neverthrow";

describe("AdministrationModalNotifications", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof AdministrationModalNotifications>> {
        return shallowMount(AdministrationModalNotifications, {
            props: {
                item: new ItemBuilder(123).build(),
                table: new ApprovalTableBuilder(35).build(),
                is_doing_something: false,
                table_notification_value: "disabled",
                is_sending_notification: false,
                table_do_reminder_value: false,
                table_reminder_occurence_value: 1,
                table_reminder_occurence_unit_value: "day",
            },
            global: {
                ...getGlobalTestOptions({}),
            },
        });
    }

    it("Should call api when clicking on send notification", async () => {
        const postApprovalTableReminder = vi
            .spyOn(rest_querier, "postApprovalTableReminder")
            .mockReturnValue(okAsync(null));
        const wrapper = getWrapper();

        await wrapper.find("[data-test=send-notification-button]").trigger("click");

        expect(postApprovalTableReminder).toHaveBeenCalledWith(123);
        expect(wrapper.emitted()).toHaveProperty("success-message");
    });
});
