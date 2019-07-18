/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

export { adjustItemToContentAfterItemCreation };

import { getItem } from "../../api/rest-querier.js";
import { flagItemAsCreated } from "./flag-item-as-created.js";

async function adjustItemToContentAfterItemCreation(context, parent, current_folder, item_id) {
    const created_item = await getItem(item_id);

    flagItemAsCreated(context, created_item);

    if (!parent.is_expanded && parent.id !== current_folder.id) {
        context.commit("addDocumentToFoldedFolder", [parent, created_item, false]);
    }
    return Promise.resolve(context.commit("addJustCreatedItemToFolderContent", created_item));
}
