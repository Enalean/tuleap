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
import { InputRule } from "prosemirror-inputrules";
import { match_emoji_regexp } from "./regexps";
import { getEmojiDB } from "./emojis-db";
import { Slice } from "prosemirror-model";

export const emojisInputRule = (): InputRule =>
    new InputRule(match_emoji_regexp, (state, match, start, end) => {
        const emoji_match = match[1].trim();
        const transaction = state.tr;

        const emoji = getEmojiDB().get(emoji_match);
        if (emoji === undefined) {
            return null;
        }

        transaction.replace(start, end, Slice.empty);
        transaction.insertText(`${emoji}`, start);

        return transaction;
    });
