/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import * as tlp from "@tuleap/tlp-fetch";
import type { ArtifactResponse } from "../type";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getArtifacts } from "./artifacts-retriever";

describe("getArtifacts", () => {
    it("retrieves a bunch of artifacts", async () => {
        const tlpGet = jest.spyOn(tlp, "get");

        const expected_artifacts = [{ id: 12 } as ArtifactResponse, { id: 14 } as ArtifactResponse];

        mockFetchSuccess(tlpGet, {
            return_json: {
                collection: expected_artifacts,
            },
        });

        const artifacts = await getArtifacts(new Set([...Array(20).keys()]));
        expect([...artifacts.values()]).toStrictEqual(expected_artifacts);
    });

    it("sends no queries when no artifacts needs to be retrieved", async () => {
        const tlpGet = jest.spyOn(tlp, "get");
        const artifacts = await getArtifacts(new Set());
        expect(artifacts.size).toStrictEqual(0);
        expect(tlpGet).not.toBeCalled();
    });
});
