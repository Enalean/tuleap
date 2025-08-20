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
import type { MockInstance } from "vitest";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import type { Modal } from "@tuleap/tlp-modal";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import HistoryVersionsContentRowForEmbeddedFile from "./HistoryVersionsContentRowForEmbeddedFile.vue";
import type { RestUser } from "../../api/rest-querier";
import type { Embedded, EmbeddedFileVersion } from "../../type";
import { FEEDBACK } from "../../injection-keys";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import * as VersionRestQuerier from "../../api/version-rest-querier";
import * as tlp_modal from "@tuleap/tlp-modal";

describe("HistoryVersionsContentRowForEmbeddedFile", () => {
    let loadVersions: () => void;
    let success: () => void;
    let deleteEmbeddedFileVersion: MockInstance;

    function getWrapper(
        item: Embedded,
        has_more_than_one_version: boolean,
    ): VueWrapper<InstanceType<typeof HistoryVersionsContentRowForEmbeddedFile>> {
        return shallowMount(HistoryVersionsContentRowForEmbeddedFile, {
            props: {
                item,
                has_more_than_one_version,
                version: {
                    number: 1,
                    name: "Plop",
                    changelog: "The changelog",
                    open_href: "/path/to/dl",
                    approval_href: "/path/to/table",
                    date: "2021-10-06",
                    author: { id: 102 } as unknown as RestUser,
                } as EmbeddedFileVersion,
                loadVersions,
            },
            global: {
                ...getGlobalTestOptions({}),
                provide: {
                    [FEEDBACK.valueOf()]: { success },
                },
            },
        });
    }

    beforeEach(() => {
        loadVersions = vi.fn();
        success = vi.fn();
        deleteEmbeddedFileVersion = vi.spyOn(VersionRestQuerier, "deleteEmbeddedFileVersion");
        vi.spyOn(tlp_modal, "createModal").mockImplementation(
            (): Modal =>
                ({
                    addEventListener: vi.fn(),
                }) as unknown as Modal,
        );
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    it("should display a link to the approval table", () => {
        const wrapper = getWrapper({ user_can_delete: true } as unknown as Embedded, true);

        expect(wrapper.find("[data-test=approval-link]").exists()).toBe(true);
    });

    it("should not display a delete button if user cannot delete", () => {
        const wrapper = getWrapper({ user_can_delete: false } as unknown as Embedded, true);

        expect(wrapper.find("[data-test=delete-button]").exists()).toBe(false);
    });

    it("should display a disabled button if user can delete but there is only one version", () => {
        const wrapper = getWrapper({ user_can_delete: true } as unknown as Embedded, false);

        const button = wrapper.find("[data-test=delete-button]").element;
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find button");
        }

        expect(button.disabled).toBe(true);
    });

    it("should display a delete button if user can delete and there is more than one version", () => {
        const wrapper = getWrapper({ user_can_delete: true } as unknown as Embedded, true);

        const button = wrapper.find("[data-test=delete-button]").element;
        if (!(button instanceof HTMLButtonElement)) {
            throw Error("Unable to find button");
        }

        expect(button.disabled).toBe(false);
    });

    it("should delete the version if user confirm the deletion and reload the versions to display latest data", async () => {
        deleteEmbeddedFileVersion.mockReturnValue(okAsync(null));

        const wrapper = getWrapper({ user_can_delete: true } as unknown as Embedded, true);

        await wrapper.find("[data-test=confirm-button]").trigger("click");

        expect(deleteEmbeddedFileVersion).toHaveBeenCalled();
        expect(success).toHaveBeenCalled();
        expect(loadVersions).toHaveBeenCalled();
    });

    it("should not reload anything if deletion of version failed", async () => {
        deleteEmbeddedFileVersion.mockReturnValue(errAsync(Fault.fromMessage("Oops!")));

        const wrapper = getWrapper({ user_can_delete: true } as unknown as Embedded, true);

        await wrapper.find("[data-test=confirm-button]").trigger("click");

        expect(deleteEmbeddedFileVersion).toHaveBeenCalled();
        expect(success).not.toHaveBeenCalled();
        expect(loadVersions).not.toHaveBeenCalled();
    });
});
