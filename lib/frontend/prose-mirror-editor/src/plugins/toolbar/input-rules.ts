/*
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

import type { Plugin } from "prosemirror-state";
import { wrappingInputRule, inputRules, textblockTypeInputRule } from "prosemirror-inputrules";
import type { InputRule } from "prosemirror-inputrules";
import type { NodeType, Schema } from "prosemirror-model";
import { automagicLinksInputRule } from "../automagic-links";

function codeBlockRule(nodeType: NodeType): InputRule {
    return textblockTypeInputRule(/^```$/, nodeType);
}

function orderedListRule(nodeType: NodeType): InputRule {
    return wrappingInputRule(
        /^(\d+)\.\s$/,
        nodeType,
        (match) => ({ order: Number(match[1]) }),
        (match, node) => node.childCount + node.attrs.order === Number(match[1]),
    );
}

function bulletListRule(nodeType: NodeType): InputRule {
    return wrappingInputRule(/^\s*([-+*])\s$/, nodeType);
}

export function buildInputRules(schema: Schema): Plugin {
    return inputRules({
        rules: [
            codeBlockRule(schema.nodes.code_block),
            orderedListRule(schema.nodes.ordered_list),
            bulletListRule(schema.nodes.bullet_list),
            automagicLinksInputRule(),
        ],
    });
}
