/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Transaction } from "prosemirror-state";
import type { UpdatedCrossReference } from "../../../helpers/UpdatedCrossReferenceTransactionDispatcher";
import type { FindCrossReferenceDecoration } from "./CrossReferenceDecorationFinder";
import type { ReplaceCrossReferenceDecoration } from "./CrossReferenceDecorationReplacer";

export type HandleCrossReferenceUpdate = {
    handle(updated_cross_reference: UpdatedCrossReference | null): Transaction | null;
};

export const UpdateCrossReferenceHandler = (
    find_decoration: FindCrossReferenceDecoration,
    replace_decoration: ReplaceCrossReferenceDecoration,
): HandleCrossReferenceUpdate => ({
    handle: (updated_cross_reference: UpdatedCrossReference | null): Transaction | null => {
        if (!updated_cross_reference) {
            return null;
        }

        const decoration_to_replace = find_decoration.findFirstDecorationAtCursorPosition(
            updated_cross_reference.position,
        );
        if (!decoration_to_replace) {
            return null;
        }

        return replace_decoration.replace(decoration_to_replace, updated_cross_reference);
    },
});
