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

import type { MarkType, Mark, Schema } from "prosemirror-model";
import type { MarkAfterEnterKeyBuilder, RegexpToMarkMapEntry } from "./index";
import { match_single_reference_regexp } from "../cross-references/regexps";
import { match_single_https_url_regexp } from "../automagic-links/regexps";

const getAutomagicLinksAfterEnterKeyBuilder = (link: MarkType): RegexpToMarkMapEntry => [
    match_single_https_url_regexp,
    {
        type: link,
        buildFromText: (text: string): Mark => link.create({ href: text }),
        canInsert: () => true,
    },
];

const getCrossReferenceAfterEnterKeyBuilder = (
    schema: Schema,
    project_id: number,
): RegexpToMarkMapEntry => {
    const { async_cross_reference, link } = schema.marks;

    return [
        match_single_reference_regexp,
        {
            type: async_cross_reference,
            buildFromText: (text: string): Mark =>
                async_cross_reference.create({ text, project_id }),
            canInsert: (node) => !link.isInSet(node.marks),
        },
    ];
};

export const buildAddMarkAfterEnterPluginMap = (
    schema: Schema,
    project_id: number,
): Map<RegExp, MarkAfterEnterKeyBuilder> =>
    new Map([
        getAutomagicLinksAfterEnterKeyBuilder(schema.marks.link),
        getCrossReferenceAfterEnterKeyBuilder(schema, project_id),
    ]);
