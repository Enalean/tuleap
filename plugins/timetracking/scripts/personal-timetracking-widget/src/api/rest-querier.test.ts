/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { getTrackedTimes, postTime, delTime } from "./rest-querier";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import { okAsync } from "neverthrow";
import { uri } from "@tuleap/fetch-result";
import * as fetch_result from "@tuleap/fetch-result";

describe("getTrackedTimes() -", (): void => {
    it("the REST API will be queried with ISO-8601 dates and the times returned", async (): Promise<void> => {
        const limit = 1,
            offset = 0,
            user_id = 102;

        const time: PersonalTime[] = [
            {
                minutes: 20,
            },
        ] as PersonalTime[];

        vi.spyOn(fetch_result, "head").mockReturnValue(
            okAsync({
                ok: true,
                headers: {
                    get: (header_name: string): string | null =>
                        header_name !== "X-PAGINATION-SIZE" ? null : String(time.length),
                },
            } as unknown as Response),
        );

        const getJSON = vi.spyOn(fetch_result, "getJSON").mockReturnValue(okAsync(time));

        const result = await getTrackedTimes(user_id, "2018-03-08", "2018-03-15", limit, offset);

        if (!result.isOk()) {
            throw new Error("Expected an Ok");
        }

        expect(getJSON).toHaveBeenCalledWith(uri`/api/v1/users/${user_id}/timetracking`, {
            params: {
                limit,
                offset,
                query: JSON.stringify({
                    start_date: "2018-03-08T00:00:00Z",
                    end_date: "2018-03-15T00:00:00Z",
                }),
            },
        });

        expect(result.value.times).toStrictEqual(time);
        expect(result.value.total).toBe(1);
    });

    it("the REST API will add date and the new time should be returned", async (): Promise<void> => {
        const time = {
            minutes: 20,
        } as PersonalTime;

        const postJSON = vi.spyOn(fetch_result, "postJSON").mockReturnValue(okAsync(time));
        const result = await postTime("2018-03-08", 2, "11:11", "oui");

        if (!result.isOk()) {
            throw new Error("Expected an Ok");
        }

        expect(postJSON).toHaveBeenCalledWith(uri`/api/v1/timetracking`, {
            date_time: "2018-03-08",
            artifact_id: 2,
            time_value: "11:11",
            step: "oui",
        });

        expect(result.value).toStrictEqual(time);
    });

    it("the REST API should delete the given time", async (): Promise<void> => {
        const tlpDel = vi
            .spyOn(fetch_result, "del")
            .mockReturnValue(okAsync({ ok: true } as unknown as Response));
        const time_id = 2;
        await delTime(time_id);

        expect(tlpDel).toHaveBeenCalledWith(uri`/api/v1/timetracking/${time_id}`);
    });
});
