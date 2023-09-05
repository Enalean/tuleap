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
import type { Modal } from "@tuleap/tlp-modal";

jest.mock("@tuleap/tlp-modal", () => {
    return {
        createModal: (): Modal =>
            ({
                addEventListener: jest.fn(),
            }) as unknown as Modal,
    };
});

const deleteFileVersion = jest.fn();
jest.mock("../../api/version-rest-querier", () => {
    return {
        deleteFileVersion,
    };
});

import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import UserBadge from "../User/UserBadge.vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import HistoryVersionsContentRow from "./HistoryVersionsContentRow.vue";
import type { RestUser } from "../../api/rest-querier";
import type { FileHistory, Item, ItemFile } from "../../type";
import { FEEDBACK } from "../../injection-keys";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import * as strict_inject from "@tuleap/vue-strict-inject";

describe("HistoryVersionsContentRow", () => {
    let location: Pick<Location, "reload">;
    let loadVersions: () => void;
    let success: () => void;

    function getWrapper(
        item: Item,
        has_more_than_one_version: boolean,
        authoring_tool = "",
    ): VueWrapper<InstanceType<typeof HistoryVersionsContentRow>> {
        jest.spyOn(strict_inject, "strictInject").mockReturnValue(true);
        return shallowMount(HistoryVersionsContentRow, {
            props: {
                item,
                has_more_than_one_version,
                version: {
                    number: 1,
                    name: "Plop",
                    changelog: "The changelog",
                    filename: "duck.png",
                    download_href: "/path/to/dl",
                    approval_href: "/path/to/table",
                    date: "2021-10-06",
                    author: { id: 102 } as unknown as RestUser,
                    coauthors: [
                        { id: 103 } as unknown as RestUser,
                        { id: 104 } as unknown as RestUser,
                    ],
                    authoring_tool,
                } as FileHistory,
                location,
                loadVersions,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [FEEDBACK as symbol]: { success },
                },
            },
        });
    }

    beforeEach(() => {
        location = { reload: jest.fn() };
        loadVersions = jest.fn();
        success = jest.fn();
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    it("should display a user badge for each author and two coauthors", () => {
        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true);

        expect(wrapper.findAllComponents(UserBadge)).toHaveLength(3);
    });

    it("should display a link to the approval table", () => {
        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true);

        expect(wrapper.find("[data-test=approval-link]").exists()).toBe(true);
    });

    it("should display authoring tool as source", () => {
        const authoring_tool = "Awesome Office Editor";

        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true, authoring_tool);

        expect(wrapper.find("[data-test=source]").text()).toBe(authoring_tool);
    });

    it("should display a 'Uploaded' as source if version has no identified authoring tool", () => {
        const authoring_tool = "";

        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true, authoring_tool);

        expect(wrapper.find("[data-test=source]").text()).toBe("Uploaded");
    });

    it("should not display a delete button if user cannot delete", () => {
        const wrapper = getWrapper({ user_can_delete: false } as ItemFile, true);

        expect(wrapper.find("[data-test=delete-button]").exists()).toBe(false);
    });

    it("should display a disabled button if user can delete but there is only one version", () => {
        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, false);

        const button = wrapper.find("[data-test=delete-button]").element;
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find button");
        }

        expect(button.disabled).toBe(true);
    });

    it("should display a delete button if user can delete and there is more than one version", () => {
        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true);

        const button = wrapper.find("[data-test=delete-button]").element;
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find button");
        }

        expect(button.disabled).toBe(false);
    });

    it("should delete the version if user confirm the deletion and reload the versions to display latest data", async () => {
        deleteFileVersion.mockReturnValue(okAsync(null));

        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true);

        await wrapper.find("[data-test=confirm-button]").trigger("click");

        expect(deleteFileVersion).toHaveBeenCalled();
        expect(success).toHaveBeenCalled();
        expect(loadVersions).toHaveBeenCalled();
    });

    it("should not reload anything if deletion of version failed", async () => {
        deleteFileVersion.mockReturnValue(errAsync(Fault.fromMessage("Oops!")));

        const wrapper = getWrapper({ user_can_delete: true } as ItemFile, true);

        await wrapper.find("[data-test=confirm-button]").trigger("click");

        expect(deleteFileVersion).toHaveBeenCalled();
        expect(success).not.toHaveBeenCalled();
        expect(loadVersions).not.toHaveBeenCalled();
    });
});
