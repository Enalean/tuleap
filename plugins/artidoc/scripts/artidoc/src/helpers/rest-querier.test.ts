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
import { errAsync, okAsync } from "neverthrow";
import * as fetch from "@tuleap/fetch-result";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import {
    getAllSections,
    getSection,
    getTracker,
    getVersion,
    getVersionedSections,
    putSection,
} from "@/helpers/rest-querier";
import { flushPromises } from "@vue/test-utils";
import type { ArtidocSection } from "@/helpers/artidoc-section.type";
import { Fault } from "@tuleap/fault";
import { uri } from "@tuleap/fetch-result";
import FreetextSectionFactory from "@/helpers/freetext-section.factory";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";

const DOCUMENT_ID = 123;
const VERSION_ID = 45210;

describe("rest-querier", () => {
    describe("getAllSections", () => {
        it("should returns retrieved sections", async () => {
            const section_a = ArtifactSectionFactory.create();
            const section_b = ArtifactSectionFactory.create();
            vi.spyOn(fetch, "getAllJSON").mockReturnValue(
                okAsync([
                    ArtifactSectionFactory.override({
                        ...section_a,
                        title: "Le title A",
                    }),
                    ArtifactSectionFactory.override({
                        ...section_b,
                        title: "Le title B",
                    }),
                ]),
            );

            let all_sections: readonly ArtidocSection[] = [];
            getAllSections(DOCUMENT_ID).match(
                (sections) => {
                    all_sections = sections;
                },
                () => {
                    throw new Error();
                },
            );

            await flushPromises();

            expect(all_sections).toHaveLength(2);
            expect(all_sections[0].title).toBe("Le title A");
            expect(all_sections[1].title).toBe("Le title B");
        });

        it("should returns retrieved sections including freetext section", async () => {
            const section_a = FreetextSectionFactory.create();
            const section_b = ArtifactSectionFactory.create();
            vi.spyOn(fetch, "getAllJSON").mockReturnValue(
                okAsync([
                    FreetextSectionFactory.override({
                        ...section_a,
                        title: "Le title A",
                    }),
                    ArtifactSectionFactory.override({
                        ...section_b,
                        title: "Le title B",
                    }),
                ]),
            );

            let all_sections: readonly ArtidocSection[] = [];
            getAllSections(DOCUMENT_ID).match(
                (sections) => {
                    all_sections = sections;
                },
                () => {
                    throw new Error();
                },
            );

            await flushPromises();

            expect(all_sections).toHaveLength(2);
            expect(all_sections[0].title).toBe("Le title A");
            expect(all_sections[1].title).toBe("Le title B");
        });
    });

    describe("getSection", () => {
        it("should returns retrieved section", async () => {
            const section = ArtifactSectionFactory.create();
            vi.spyOn(fetch, "getJSON").mockReturnValue(
                okAsync(
                    ArtifactSectionFactory.override({
                        ...section,
                        title: "Le title A",
                    }),
                ),
            );

            let retrieved_section: ArtidocSection = ArtifactSectionFactory.create();
            getSection("section-id").match(
                (section) => {
                    retrieved_section = section;
                },
                () => {
                    throw new Error();
                },
            );

            await flushPromises();

            expect(retrieved_section.title).toBe("Le title A");
        });

        it("should returns retrieved freetext section", async () => {
            const section = FreetextSectionFactory.create();
            vi.spyOn(fetch, "getJSON").mockReturnValue(
                okAsync(
                    FreetextSectionFactory.override({
                        ...section,
                        title: "Le title A",
                    }),
                ),
            );

            let retrieved_section: ArtidocSection = FreetextSectionFactory.create();
            getSection("section-id").match(
                (section) => {
                    retrieved_section = section;
                },
                () => {
                    throw new Error();
                },
            );

            await flushPromises();

            expect(retrieved_section.title).toBe("Le title A");
        });
    });

    describe("putSection", () => {
        it("should update freetext", async () => {
            const put = vi
                .spyOn(fetch, "putResponse")
                .mockReturnValue(errAsync(Fault.fromMessage("OSEF")));

            putSection("123", "New title", "New description", [], 1);

            await flushPromises();

            expect(put).toHaveBeenCalledWith(
                uri`/api/artidoc_sections/123`,
                {},
                {
                    title: "New title",
                    description: "New description",
                    attachments: [],
                    level: 1,
                },
            );
        });
    });

    describe("getTracker", () => {
        it("should return tracker information", async () => {
            const field_1 = {
                field_id: 599,
                label: "Summary",
                type: "text",
            } as ConfigurationField;
            const field_2 = {
                field_id: 602,
                label: "String Field",
                type: "text",
            } as ConfigurationField;
            const semantics = { title: { field_id: 599 } };

            vi.spyOn(fetch, "getJSON").mockReturnValue(
                okAsync({
                    fields: [field_1, field_2],
                    semantics: semantics,
                }),
            );

            const result = await getTracker(24);
            if (!result.isOk()) {
                throw Error("Expected an Ok");
            }

            expect(result.value.fields).toStrictEqual([field_1, field_2]);
        });
    });

    describe("getVersionedSections", () => {
        it("should returns retrieved versioned sections", async () => {
            const section_a = FreetextSectionFactory.create();
            const section_b = ArtifactSectionFactory.create();

            vi.spyOn(fetch, "getAllJSON").mockReturnValue(
                okAsync([
                    FreetextSectionFactory.override({
                        ...section_a,
                        title: "Le title A",
                    }),
                    ArtifactSectionFactory.override({
                        ...section_b,
                        title: "Le title B",
                    }),
                ]),
            );

            let all_sections: readonly ArtidocSection[] = [];
            getVersionedSections(DOCUMENT_ID, VERSION_ID).match(
                (sections) => {
                    all_sections = sections;
                },
                () => {
                    throw new Error();
                },
            );

            await flushPromises();

            expect(fetch.getAllJSON).toHaveBeenCalledWith(
                uri`/api/artidoc_versions/${VERSION_ID}`,
                {
                    params: {
                        limit: 50,
                        artidoc_id: DOCUMENT_ID,
                    },
                },
            );
            expect(all_sections).toHaveLength(2);
            expect(all_sections[0].title).toBe("Le title A");
            expect(all_sections[1].title).toBe("Le title B");
        });
    });

    describe("getVersion", () => {
        it("should return the version", async () => {
            const version_payload = {
                id: VERSION_ID,
                created_on: "2025-11-24T15:30:00+01:00",
                created_by: {
                    id: 102,
                    user_url: "example.com",
                    avatar_url: "example.com",
                    display_name: "John Doe",
                },
            };

            vi.spyOn(fetch, "getJSON").mockReturnValue(okAsync([version_payload]));

            const result = await getVersion(DOCUMENT_ID, VERSION_ID);

            expect(fetch.getJSON).toHaveBeenCalledWith(
                uri`/api/v1/artidoc/${DOCUMENT_ID}/versions`,
                {
                    params: {
                        query: JSON.stringify({ versions_ids: [VERSION_ID] }),
                    },
                },
            );
            if (!result.isOk()) {
                throw new Error("Expected an Ok");
            }

            expect(result.value.id).toBe(version_payload.id);
            expect(result.value.title.isNothing()).toBe(true);
            expect(result.value.description.isNothing()).toBe(true);
            expect(result.value.created_by).toBe(version_payload.created_by);
            expect(result.value.created_on).toStrictEqual(new Date(version_payload.created_on));
        });
    });
});
