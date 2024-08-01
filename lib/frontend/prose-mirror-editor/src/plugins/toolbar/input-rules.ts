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
import type { InputRule } from "prosemirror-inputrules";
import { inputRules, textblockTypeInputRule } from "prosemirror-inputrules";
import type { NodeType, Schema } from "prosemirror-model";

export function codeBlockRule(nodeType: NodeType): InputRule {
    return textblockTypeInputRule(/^```$/, nodeType);
}

export function buildInputRules(schema: Schema): Plugin {
    return inputRules({ rules: [codeBlockRule(schema.nodes.code_block)] });
}
