/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { okAsync, type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import * as fetch_result from "@tuleap/fetch-result";
import { uri } from "@tuleap/fetch-result";
import { InitializationAPIClient } from "./InitializationAPIClient";
import type { CurrentArtifactWithTrackerStructure } from "../../../domain/initialization/CurrentArtifactWithTrackerStructure";
import { CurrentArtifactIdentifier } from "../../../domain/CurrentArtifactIdentifier";

describe(`InitializationAPIClient`, () => {
    const getClient = (): ReturnType<typeof InitializationAPIClient> => InitializationAPIClient();

    describe(`getCurrentArtifactWithTrackerStructure()`, () => {
        const ARTIFACT_ID = 40;

        const getArtifact = (): ResultAsync<CurrentArtifactWithTrackerStructure, Fault> => {
            return getClient().getCurrentArtifactWithTrackerStructure(
                CurrentArtifactIdentifier.fromId(ARTIFACT_ID),
            );
        };

        it(`will return the current artifact with its tracker structure matching the given artifact id`, async () => {
            const values: ReadonlyArray<unknown> = [
                { field_id: 866, label: "unpredisposed", value: "ectogenous" },
                { field_id: 468, label: "coracler", value: "caesaropapism" },
            ];
            const artifact = {
                id: ARTIFACT_ID,
                title: "coincoin",
                values: values,
            } as CurrentArtifactWithTrackerStructure;
            const getResponse = jest.spyOn(fetch_result, "getResponse");
            getResponse.mockReturnValue(
                okAsync({
                    headers: new Headers({ Etag: "1607498311", "Last-Modified": "1607498311" }),
                    json: () => Promise.resolve(artifact),
                } as Response),
            );

            const result = await getArtifact();

            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }
            expect(result.value.etag).toBe("1607498311");
            expect(result.value.last_modified).toBe("1607498311");
            expect(result.value).toStrictEqual(expect.objectContaining(artifact));
            expect(getResponse.mock.calls[0][0]).toStrictEqual(
                uri`/api/v1/artifacts/${ARTIFACT_ID}?tracker_structure_format=complete`,
            );
        });
    });
});
