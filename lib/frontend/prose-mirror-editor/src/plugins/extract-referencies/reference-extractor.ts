/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 *
 */

import type { ResultAsync } from "neverthrow";
import { okAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { postJSON, uri } from "@tuleap/fetch-result";

export interface CrossReference {
    text: string;
    link: string;
    context: string;
}

export function getNodeText(
    new_text_input: string,
    project_id: number,
): ResultAsync<Array<CrossReference>, Fault> {
    if (hasAReference(new_text_input)) {
        return getTextWithCrossReferences(new_text_input, project_id);
    }
    return okAsync([]);
}

export function hasAReference(text: string): boolean {
    return /\w+\s#[\w\-:./]+/.test(text);
}

function getTextWithCrossReferences(
    text: string,
    project_id: number,
): ResultAsync<Array<CrossReference>, Fault> {
    return postJSON(uri`/api/v1/projects/${project_id}/extract_references`, {
        text,
    });
}
