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

import { Plugin, PluginKey } from "prosemirror-state";
import type { Mark, MarkType } from "prosemirror-model";

export type MarkAfterEnterKeyBuilder = {
    type: MarkType;
    buildFromText: (text: string) => Mark;
};

export type RegexpToMarkMapEntry = [RegExp, MarkAfterEnterKeyBuilder];

export const initAddMarkAfterEnterPlugin = (
    regexp_to_mark_map: Map<RegExp, MarkAfterEnterKeyBuilder>,
): Plugin =>
    new Plugin({
        key: new PluginKey("AddMarkAfterEnterPlugin"),
        props: {
            handleDOMEvents: {
                keydown: (view, event): void => {
                    if (event.key !== "Enter") {
                        return;
                    }

                    const state = view.state;
                    const { $anchor } = state.selection;

                    const node_before_cursor = $anchor.nodeBefore;
                    if (
                        !node_before_cursor ||
                        !node_before_cursor.isText ||
                        !node_before_cursor.text
                    ) {
                        return;
                    }

                    const node_text = node_before_cursor.text;

                    for (const [regexp, { type, buildFromText }] of regexp_to_mark_map.entries()) {
                        const match = regexp.exec(node_text);
                        if (!match || !match[0]) {
                            continue;
                        }

                        if (type.isInSet(node_before_cursor.marks)) {
                            continue;
                        }

                        view.dispatch(
                            state.tr.addMark(
                                $anchor.pos - match[0].length,
                                $anchor.pos,
                                buildFromText(match[0]),
                            ),
                        );

                        return;
                    }
                },
            },
        },
    });
