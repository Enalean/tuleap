/*
 *  Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import * as lock_rest_querier from "../../api/lock-rest-querier";
import * as rest_querier from "../../api/rest-querier";
import { TYPE_EMBEDDED, TYPE_FILE } from "../../constants";
import { mockFetchError } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { lockDocument, unlockDocument } from "./lock-actions";

describe("lock", () => {
    let postLockFile, getItem, context;

    beforeEach(() => {
        context = { commit: jest.fn() };

        postLockFile = jest
            .spyOn(lock_rest_querier, "postLockFile")
            .mockReturnValue(Promise.resolve());
        jest.spyOn(lock_rest_querier, "postLockEmbedded").mockReturnValue(Promise.resolve());
        getItem = jest.spyOn(rest_querier, "getItem");
    });

    it("should lock a file and then update its information", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_FILE,
        };

        getItem.mockReturnValue(
            Promise.resolve({
                id: 123,
                title: "My file",
                type: TYPE_FILE,
                lock_info: {
                    user_id: 123,
                },
            })
        );

        await lockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("replaceLockInfoWithNewVersion", [
            item_to_lock,
            { user_id: 123 },
        ]);
    });

    it("should raise a translated exception when user can't lock a document", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_FILE,
        };

        mockFetchError(postLockFile, {
            status: 400,
            error_json: {
                error: {
                    i18n_error_message: "Item is already locked",
                },
            },
        });

        await lockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("error/setLockError", "Item is already locked");
    });

    it("should raise a generic error message when no information is given when user can't lock a document", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_FILE,
        };

        mockFetchError(postLockFile, {
            status: 400,
        });

        await expect(lockDocument(context, item_to_lock)).rejects.toBeDefined();
        expect(context.commit).toHaveBeenCalledWith("error/setLockError", "Internal server error");
    });

    it("should lock an embedded file and then update its information", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_EMBEDDED,
        };

        getItem.mockReturnValue(
            Promise.resolve({
                id: 123,
                title: "My embedded",
                type: TYPE_EMBEDDED,
                lock_info: {
                    user_id: 123,
                },
            })
        );

        await lockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("replaceLockInfoWithNewVersion", [
            item_to_lock,
            { user_id: 123 },
        ]);
    });
});

describe("unlock", () => {
    let getItem, context;

    beforeEach(() => {
        context = { commit: jest.fn() };

        jest.spyOn(lock_rest_querier, "deleteLockFile").mockReturnValue(Promise.resolve());
        jest.spyOn(lock_rest_querier, "deleteLockEmbedded").mockReturnValue(Promise.resolve());
        getItem = jest.spyOn(rest_querier, "getItem");
    });

    it("should unlock a file and then update its information", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_FILE,
        };

        getItem.mockReturnValue(
            Promise.resolve({
                id: 123,
                title: "My file",
                type: TYPE_FILE,
                lock_info: {
                    user_id: 123,
                },
            })
        );

        await unlockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("replaceLockInfoWithNewVersion", [
            item_to_lock,
            { user_id: 123 },
        ]);
    });

    it("should unlock an embedded file and then update its information", async () => {
        const item_to_lock = {
            id: 123,
            title: "My file",
            type: TYPE_EMBEDDED,
        };

        getItem.mockReturnValue(
            Promise.resolve({
                id: 123,
                title: "My embedded",
                type: TYPE_EMBEDDED,
                lock_info: {
                    user_id: 123,
                },
            })
        );

        await unlockDocument(context, item_to_lock);

        expect(context.commit).toHaveBeenCalledWith("replaceLockInfoWithNewVersion", [
            item_to_lock,
            { user_id: 123 },
        ]);
    });
});
