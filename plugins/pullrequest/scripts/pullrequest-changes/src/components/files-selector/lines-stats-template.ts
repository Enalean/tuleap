/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

import type { HTMLTemplateStringProcessor, HTMLTemplateResult } from "@tuleap/lazybox";
import type { PullRequestFile } from "../../api/rest-querier";

export const getLinesStatsTemplate = (
    html: typeof HTMLTemplateStringProcessor,
    file: PullRequestFile,
): HTMLTemplateResult => html`
    <span
        data-test="added-lines"
        class="pull-request-file-changes pull-request-file-lines-added tlp-text-success"
    >
        ${file.lines_added.mapOr((nb) => `+${nb}`, "")}
    </span>
    <span
        data-test="removed-lines"
        class="pull-request-file-changes pull-request-file-lines-removed tlp-text-danger"
    >
        ${file.lines_removed.mapOr((nb) => `-${nb}`, "")}
    </span>
`;
