/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import { beforeEach, describe, expect, it, vi } from "vitest";

import * as rest_querier from "../../api/rest-querier";
import { uploadNewVersion } from "./upload-new-version";
import { uploadVersion } from "./upload-file";

import type { ActionContext } from "vuex";
import type { ApprovalTable, ItemFile, RootState } from "../../type";
import { StateBuilder } from "../../../tests/builders/StateBuilder";
import { ItemBuilder } from "../../../tests/builders/ItemBuilder";

vi.mock("../../api/rest-querier");
vi.mock("./upload-file");

describe("uploadNewVersion", () => {
    let context: ActionContext<RootState, RootState>;
    let create_new_version: MockInstance;

    beforeEach(() => {
        context = {
            commit: vi.fn(),
            state: new StateBuilder().build(),
        } as unknown as ActionContext<RootState, RootState>;

        create_new_version = vi.spyOn(rest_querier, "createNewVersion");
        vi.clearAllMocks();
    });

    it("creates a new version and triggers uploadVersion when file is not empty", async () => {
        const item = new ItemBuilder(10).withType("file").build() as ItemFile;
        const uploaded_file = new File(["abc"], "file.txt", { type: "text/plain" });
        const version_title = "v2";
        const changelog = "Updated content";
        const is_file_locked = false;
        const approval_table_action = {} as ApprovalTable;

        const returned_version = { upload_href: "/upload" };
        create_new_version.mockResolvedValue(returned_version);

        await uploadNewVersion(context, [
            item,
            uploaded_file,
            version_title,
            changelog,
            is_file_locked,
            approval_table_action,
        ]);

        expect(create_new_version).toHaveBeenCalledWith(
            item,
            version_title,
            changelog,
            uploaded_file,
            is_file_locked,
            approval_table_action,
        );

        expect(context.commit).toHaveBeenCalledWith("addFileInUploadsList", item);
        expect(context.commit).toHaveBeenCalledWith("setNewVersionUploadState", {
            item_id: item.id,
            is_uploading_new_version: true,
        });

        expect(uploadVersion).toHaveBeenCalledWith(context, uploaded_file, item, returned_version);
    });
});
