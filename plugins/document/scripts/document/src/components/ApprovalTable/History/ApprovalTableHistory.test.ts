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
import { RouterLinkStub, shallowMount } from "@vue/test-utils";
import ApprovalTableHistory from "./ApprovalTableHistory.vue";
import { ItemBuilder } from "../../../../tests/builders/ItemBuilder";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";
import * as rest_querier from "../../../api/approval-table-rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { ApprovalTableBuilder } from "../../../../tests/builders/ApprovalTableBuilder";

vi.useFakeTimers();
vi.mock("vue-router");

describe("ApprovalTableHistory", () => {
    function getWrapper(): VueWrapper<InstanceType<typeof ApprovalTableHistory>> {
        return shallowMount(ApprovalTableHistory, {
            props: { item: new ItemBuilder(123).buildApprovableDocument(), version: 2 },
            global: {
                ...getGlobalTestOptions({}),
                stubs: {
                    RouterLink: RouterLinkStub,
                },
            },
        });
    }

    it("Should emit error when API fails", async () => {
        const getAll = vi
            .spyOn(rest_querier, "getAllDocumentApprovalTables")
            .mockReturnValue(errAsync(Fault.fromMessage("Oh no!")));

        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(getAll).toHaveBeenCalledWith(123);
        const emitted = wrapper.emitted("error");
        if (emitted === undefined) {
            throw new Error("Expected to find some events");
        }
        expect(emitted[0][0]).toBe("Oh no!");
    });

    it("Should display all approval tables", async () => {
        const version_label = "Final final vFinal last 2.0";
        const getAll = vi
            .spyOn(rest_querier, "getAllDocumentApprovalTables")
            .mockReturnValue(
                okAsync([
                    new ApprovalTableBuilder(35)
                        .withVersionNumber(2)
                        .withVersionLabel(version_label)
                        .build(),
                    new ApprovalTableBuilder(34).withVersionNumber(1).build(),
                ]),
            );

        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(getAll).toHaveBeenCalledWith(123);
        const rows = wrapper.findAll("[data-test=history-row]");
        expect(rows).toHaveLength(2);
        // Row 0
        expect(rows[0].find("[data-test=history-row-number]").text()).toBe("2");
        expect(
            rows[0].find("[data-test=history-row-number]").findComponent(RouterLinkStub).exists(),
        ).toBe(false);
        expect(rows[0].find("[data-test=history-row-label]").text()).toBe(version_label);
        // Row 1
        expect(rows[1].find("[data-test=history-row-number]").text()).toBe("1");
        expect(
            rows[1].find("[data-test=history-row-number]").findComponent(RouterLinkStub).exists(),
        ).toBe(true);
        expect(rows[1].find("[data-test=history-row-label]").text()).toBe("");
    });
});
