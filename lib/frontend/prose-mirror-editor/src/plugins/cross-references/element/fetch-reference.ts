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

import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { postJSON, uri } from "@tuleap/fetch-result";

type CrossReference = {
    link: string;
};

export const fetchReferencesInText = (
    text: string,
    project_id: number,
): ResultAsync<Array<CrossReference> | undefined, Fault> => {
    return postJSON(uri`/api/v1/projects/${project_id}/extract_references`, {
        text,
    });
};
