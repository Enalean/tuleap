/*
 *  Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { InputRule } from "prosemirror-inputrules";
import { match_newly_typed_https_url_regexp } from "./regexps";

export const automagicLinksInputRule = (): InputRule =>
    new InputRule(match_newly_typed_https_url_regexp, (state, match, start, end) => {
        const url = match[0].trim();
        const transaction = state.tr;

        transaction.addMark(start, end, state.schema.marks.link.create({ href: url }));
        transaction.insertText(" ", end);

        return transaction;
    });
