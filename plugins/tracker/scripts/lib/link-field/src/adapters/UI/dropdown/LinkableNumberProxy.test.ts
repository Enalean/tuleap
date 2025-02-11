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

import { beforeEach, describe, expect, it } from "vitest";
import { Option } from "@tuleap/option";
import { CurrentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import { LinkableNumberProxy } from "./LinkableNumberProxy";
import type { LinkableNumber } from "../../../domain/links/LinkableNumber";

describe("LinkableNumberProxy", () => {
    let current_artifact_option: Option<CurrentArtifactIdentifier>;

    beforeEach(() => {
        current_artifact_option = Option.nothing();
    });

    const build = (query: string): Option<LinkableNumber> =>
        LinkableNumberProxy.fromQueryString(query, current_artifact_option);

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
    ])("should return nothing when %s is entered", (query) => {
        expect(build(query).isNothing()).toBe(true);
    });

    it("should return nothing when the user has entered the current artifact_id", () => {
        current_artifact_option = Option.fromValue(CurrentArtifactIdentifier.fromId(105));
        expect(build("105").isNothing()).toBe(true);
    });

    it("should return a LinkableNumber", () => {
        const linkable_number = build("105");
        expect(linkable_number.unwrapOr(null)?.id).toBe(105);
    });
});
