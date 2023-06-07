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
import type { ActionContext, ActionTree } from "vuex";
import type { Item, State, RootState } from "../../type";
import { isEmbedded, isEmpty, isFile, isLink, isWiki } from "../../helpers/type-check-helper";

export interface LockActions extends ActionTree<State, RootState> {
    readonly lockDocument: typeof lockDocument;
    readonly unlockDocument: typeof unlockDocument;
}

export const lockDocument = async (
    context: ActionContext<State, State>,
    item: Item
): Promise<void> => {
    try {
        if (isFile(item)) {
            await postLockFile(item);
        } else if (isEmbedded(item)) {
            await postLockEmbedded(item);
        } else if (isWiki(item)) {
            await postLockWiki(item);
        } else if (isLink(item)) {
            await postLockLink(item);
        } else if (isEmpty(item)) {
            await postLockEmpty(item);
        }

        const updated_item = await getItem(item.id);
        item.lock_info = updated_item.lock_info;
        context.commit("replaceFolderContentByItem", updated_item, { root: true });
    } catch (exception) {
        await context.dispatch("error/handleErrorsForLock", exception, { root: true });
    }
};

export const unlockDocument = async (
    context: ActionContext<State, State>,
    item: Item
): Promise<void> => {
    try {
        if (isFile(item)) {
            await deleteLockFile(item);
        } else if (isEmbedded(item)) {
            await deleteLockEmbedded(item);
        } else if (isWiki(item)) {
            await deleteLockWiki(item);
        } else if (isLink(item)) {
            await deleteLockLink(item);
        } else if (isEmpty(item)) {
            await deleteLockEmpty(item);
        }

        const updated_item = await getItem(item.id);
        item.lock_info = updated_item.lock_info;
        context.commit("replaceFolderContentByItem", updated_item, { root: true });
    } catch (exception) {
        await context.dispatch("error/handleErrorsForLock", exception, { root: true });
    }
};
