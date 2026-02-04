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
import {
    FILE_STATUS_ADDED,
    FILE_STATUS_DELETED,
    FILE_STATUS_MODIFIED,
} from "../../api/rest-querier";

export const getStatusBadgeTemplate = (
    html: typeof HTMLTemplateStringProcessor,
    file: PullRequestFile,
): HTMLTemplateResult => {
    const classes = ["pull-request-file-status"];
    switch (file.status) {
        case FILE_STATUS_ADDED:
            classes.push("pull-request-file-status-added");
            break;
        case FILE_STATUS_MODIFIED:
            classes.push("pull-request-file-status-modified");
            break;
        case FILE_STATUS_DELETED:
            classes.push("pull-request-file-status-deleted");
            break;
        default:
            break;
    }

    return html`<span class="${classes}" data-test="file-status">${file.status}</span>`;
};
