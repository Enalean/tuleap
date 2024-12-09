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
import { InputRule } from "prosemirror-inputrules";
import { match_newly_typed_reference_regexp } from "./regexps";

export const DetectCrossReferenceAsYouTypeInputRule = (project_id: number): InputRule =>
    new InputRule(
        match_newly_typed_reference_regexp,
        (state, match, from, to): Transaction | null => {
            const text_node_at_pos = state.doc.nodeAt(from);
            if (text_node_at_pos && state.schema.marks.link.isInSet(text_node_at_pos.marks)) {
                return null;
            }

            const text = match[0].trim();
            const transaction = state.tr;

            transaction.addMark(
                from,
                to,
                state.schema.marks.async_cross_reference.create({ text, project_id }),
            );
            transaction.insertText(" ", to);

            return transaction;
        },
    );
