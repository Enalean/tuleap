/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import { LinkableNumberProxy } from "./LinkableNumberProxy";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";

describe("LinkableNumberProxy", () => {
    it.each([
        "abcd",
        "10+",
        "105d",
        "d105",
        "10^5",
        "1e5",
        "-105",
        "10.5",
        "1,05",
        "0b1101001",
        "0o151",
        "0x69",
    ])("should return null when %s is entered", (query) => {
        expect(LinkableNumberProxy.fromQueryString(query, null)).toBeNull();
    });

    it("should return null when the user has entered the current artifact_id", () => {
        expect(
            LinkableNumberProxy.fromQueryString("105", CurrentArtifactIdentifierStub.withId(105))
        ).toBeNull();
    });

    it("should return a LinkableNumber", () => {
        const linkable_number = LinkableNumberProxy.fromQueryString("105", null);
        expect(linkable_number?.id).toBe(105);
    });
});
