/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import {
    getStatusFromMapping,
    getStatusMetadata,
    getStatusIdFromName,
} from "./hardcoded-metadata-mapping-helper.js";

describe("getStatusMetadata", () => {
    it("Given metadata has a status, then its return the corresponding metadata", () => {
        const all_metadata = [{ short_name: "test" }, { short_name: "status" }];

        expect(getStatusMetadata(all_metadata)).toEqual({ short_name: "status" });
    });
});

describe("getStatusFromMapping", () => {
    it("Returns the correct status string depending on status id", () => {
        expect(getStatusFromMapping(101)).toEqual("draft");
    });

    it("Returns none if the value does not correspond to anything known", () => {
        expect(getStatusFromMapping(200)).toEqual("none");
    });
});

describe("getStatusIdFromName", () => {
    it("Returns the correct status string depending on status id", () => {
        expect(getStatusIdFromName("draft")).toEqual(101);
    });

    it("Returns none if the value does not correspond to anything known", () => {
        expect(getStatusIdFromName("aapap")).toEqual(100);
    });
});
