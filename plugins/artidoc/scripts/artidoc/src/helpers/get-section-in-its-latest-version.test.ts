/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import * as rest from "@/helpers/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import {
    isOutdatedSectionFault,
    getSectionInItsLatestVersion,
} from "@/helpers/get-section-in-its-latest-version";
import { Fault } from "@tuleap/fault";

describe("get-section-in-its-latest-version", () => {
    it.each([
        ["artifact", ArtifactSectionFactory.create()],
        ["freetext", FreetextSectionFactory.create()],
    ])(
        "should return true if retrieved %s section content is the same than the old one",
        async (name, section) => {
            vi.spyOn(rest, "getSection").mockReturnValue(okAsync(section));

            const result = await getSectionInItsLatestVersion(section);

            expect(result.isOk()).toBe(true);
        },
    );

    it.each([
        ["artifact", ArtifactSectionFactory.create()],
        ["freetext", FreetextSectionFactory.create()],
    ])(
        "should return fault if retrieved %s section title is not the same than the old one",
        async (name, section) => {
            vi.spyOn(rest, "getSection").mockReturnValue(
                okAsync({
                    ...section,
                    title: "Remotely updated title",
                }),
            );

            const result = await getSectionInItsLatestVersion({
                ...section,
                title: "Original title",
            });

            expect(result.isErr()).toBe(true);
            result.match(
                () => {},
                (fault: Fault) => expect(isOutdatedSectionFault(fault)).toBe(true),
            );
        },
    );

    it("should return fault if retrieved artifact section description is not the same than the old one", async () => {
        const section = ArtifactSectionFactory.create();

        vi.spyOn(rest, "getSection").mockReturnValue(
            okAsync({
                ...section,
                description: "Remotely updated description",
            }),
        );

        const result = await getSectionInItsLatestVersion({
            ...section,
            description: "Original description",
        });

        expect(result.isErr()).toBe(true);
        result.match(
            () => {},
            (fault: Fault) => expect(isOutdatedSectionFault(fault)).toBe(true),
        );
    });
    it("should return fault if retrieved freetext section description is not the same than the old one", async () => {
        const section = FreetextSectionFactory.create();

        vi.spyOn(rest, "getSection").mockReturnValue(
            okAsync({
                ...section,
                description: "Remotely updated description",
            }),
        );

        const result = await getSectionInItsLatestVersion({
            ...section,
            description: "Original description",
        });

        expect(result.isErr()).toBe(true);
        result.match(
            () => {},
            (fault: Fault) => expect(isOutdatedSectionFault(fault)).toBe(true),
        );
    });

    it.each([
        ["artifact", ArtifactSectionFactory.create()],
        ["freetext", FreetextSectionFactory.create()],
    ])("should return fault if %s section cannot be retrieved", async (name, section) => {
        const err = Fault.fromMessage("Not found");
        vi.spyOn(rest, "getSection").mockReturnValue(errAsync(err));

        const result = await getSectionInItsLatestVersion(section);

        expect(result.isErr()).toBe(true);
        result.match(
            () => {},
            (fault: Fault) => expect(fault).toStrictEqual(err),
        );
    });
});
