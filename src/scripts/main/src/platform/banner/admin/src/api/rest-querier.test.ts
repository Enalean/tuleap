/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { saveBannerForPlatform } from "./rest-querier";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";

describe("rest-querier", () => {
    it("saves banner without an expiration date when none is provided", async () => {
        const putSpy = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(putSpy);

        await saveBannerForPlatform("Some message", "critical", "");

        expect(putSpy).toHaveBeenCalledWith("/api/banner", {
            headers: {
                "content-type": "application/json",
            },
            body: '{"message":"Some message","importance":"critical","expiration_date":null}',
        });
    });

    it("saves banner with an expiration date", async () => {
        const putSpy = jest.spyOn(tlp_fetch, "put");
        mockFetchSuccess(putSpy);

        await saveBannerForPlatform("Some message", "critical", "2021-06-30T14:53:40.720Z");

        expect(putSpy).toHaveBeenCalledWith("/api/banner", {
            headers: {
                "content-type": "application/json",
            },
            body: '{"message":"Some message","importance":"critical","expiration_date":"2021-06-30T14:53:40Z"}',
        });
    });
});
