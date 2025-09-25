/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { getJSON, uri } from "@tuleap/fetch-result";
import type { User } from "@tuleap/core-rest-api-types";
import { Fault } from "@tuleap/fault";
import type { ResultAsync } from "neverthrow";
import { okAsync, errAsync } from "neverthrow";

export interface Version {
    readonly id: number; // should probably be a uuuid in the end
    readonly created_on: Date;
    readonly created_by: User;
    readonly title: string | null;
}

const current_date: Date = new Date();
const day_in_ms = 24 * 3600 * 1000;
const minutes_in_ms = 60 * 1000;
const minutes_offset_in_ms = (3 * 60 + 37) * minutes_in_ms; // in order to not have always the same current time in fake data

export function getVersions(project_id: number): ResultAsync<ReadonlyArray<Version>, Fault> {
    return getJSON<ReadonlyArray<User>>(uri`/api/v1/user_groups/${project_id}_3/users`).andThen(
        (project_members): ResultAsync<ReadonlyArray<Version>, Fault> => {
            if (project_members.length === 0) {
                return errAsync(Fault.fromMessage("No project members found"));
            }

            return okAsync(
                [...Array(100).keys()].map((index: number): Version => {
                    const created_on = new Date(current_date);

                    const nb_days_ago = Math.floor(Math.random() * 3);
                    current_date.setTime(
                        current_date.getTime() - nb_days_ago * day_in_ms - minutes_offset_in_ms,
                    );

                    return {
                        id: index,
                        created_on,
                        created_by:
                            project_members[Math.floor(Math.random() * project_members.length)],
                        title:
                            index === 3
                                ? "v2 Draft"
                                : index === 10
                                  ? "v1.2 Final"
                                  : index === 15
                                    ? "v1.2 Draft.Final"
                                    : index === 20
                                      ? "v1.2 Draft"
                                      : index === 25
                                        ? "v1 Final"
                                        : index === 30
                                          ? "v1 Draft"
                                          : null,
                    };
                }),
            );
        },
    );
}
