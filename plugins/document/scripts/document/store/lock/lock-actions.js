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

import { TYPE_EMBEDDED, TYPE_EMPTY, TYPE_FILE, TYPE_LINK, TYPE_WIKI } from "../../constants";
import { getItem } from "../../api/rest-querier";
import {
    deleteLockEmbedded,
    deleteLockEmpty,
    deleteLockFile,
    deleteLockLink,
    deleteLockWiki,
    postLockEmbedded,
    postLockEmpty,
    postLockFile,
    postLockLink,
    postLockWiki,
} from "../../api/lock-rest-querier";
import { handleErrorsForLock } from "../actions-helpers/handle-errors";

export const lockDocument = async (context, item) => {
    try {
        switch (item.type) {
            case TYPE_FILE:
                await postLockFile(item);
                break;
            case TYPE_EMBEDDED:
                await postLockEmbedded(item);
                break;
            case TYPE_WIKI:
                await postLockWiki(item);
                break;
            case TYPE_LINK:
                await postLockLink(item);
                break;
            case TYPE_EMPTY:
                await postLockEmpty(item);
                break;
            default:
                break;
        }

        const updated_item = await getItem(item.id);
        context.commit("replaceLockInfoWithNewVersion", [item, updated_item.lock_info]);
    } catch (exception) {
        return handleErrorsForLock(context, exception);
    }
};

export const unlockDocument = async (context, item) => {
    try {
        switch (item.type) {
            case TYPE_FILE:
                await deleteLockFile(item);
                break;
            case TYPE_EMBEDDED:
                await deleteLockEmbedded(item);
                break;
            case TYPE_WIKI:
                await deleteLockWiki(item);
                break;
            case TYPE_LINK:
                await deleteLockLink(item);
                break;
            case TYPE_EMPTY:
                await deleteLockEmpty(item);
                break;
            default:
                break;
        }
        const updated_item = await getItem(item.id);
        context.commit("replaceLockInfoWithNewVersion", [item, updated_item.lock_info]);
    } catch (exception) {
        return handleErrorsForLock(context, exception);
    }
};
