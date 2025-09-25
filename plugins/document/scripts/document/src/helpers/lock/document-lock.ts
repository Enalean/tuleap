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
import type { Item, State } from "../../type";
import type { ActionContext } from "vuex";
import { isEmbedded, isEmpty, isFile, isLink, isWiki } from "../type-check-helper";
import {
    postLockEmbedded,
    postLockEmpty,
    postLockFile,
    postLockLink,
    postLockWiki,
} from "../../api/lock-rest-querier";
import { getItem } from "../../api/rest-querier";

export type DocumentLock = {
    lockDocument(context: ActionContext<State, State>, item: Item): Promise<void>;
};

export const getDocumentLock = (): DocumentLock => ({
    async lockDocument(context: ActionContext<State, State>, item: Item): Promise<void> {
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
    },
});
