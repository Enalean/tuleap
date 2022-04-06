/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { retrieveArtifacts } from "./artifacts-retriever";
import type { Artifact } from "./artifact";

describe("artifacts-retriever", () => {
    it("Retrieves all artifacts", async () => {
        const spyTlpGet = jest.spyOn(tlp_fetch, "get");

        const expected_artifacts = [{ id: 12 } as Artifact, { id: 35 } as Artifact];

        mockFetchSuccess(spyTlpGet, {
            return_json: {
                collection: expected_artifacts,
            },
        });

        const artifacts = await retrieveArtifacts([...Array(350).keys()]);
        expect([...artifacts.values()]).toStrictEqual(expected_artifacts);
        expect(spyTlpGet).toHaveBeenCalledTimes(4);
    });
});
