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
import * as fetch from "@tuleap/fetch-result";
import { searchExistingArtifactsForAutocompleter } from "@/helpers/search-existing-artifacts-for-autocompleter";
import type { LazyAutocompleter } from "@tuleap/lazybox";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import type { Language } from "vue3-gettext";
import { okAsync } from "neverthrow";
import ArtifactSectionFactory from "@/helpers/artifact-section.factory";
import { SectionsCollectionStub } from "@/sections/stubs/SectionsCollectionStub";
import { CreateStoredSections } from "@/sections/states/CreateStoredSections";

describe("search-existing-artifacts-for-autocompleter", () => {
    it("should empty the results if query is empty", () => {
        const query = "";

        const tracker = TrackerStub.withTitleAndDescription();

        const gettext: Language = {
            $gettext: (msgid: string) => msgid,
            interpolate: (msgid: string) => msgid,
        } as unknown as Language;

        const replaceContent = vi.fn();

        const autocompleter: LazyAutocompleter = {
            replaceContent,
        } as unknown as LazyAutocompleter;

        searchExistingArtifactsForAutocompleter(
            query,
            autocompleter,
            tracker,
            tracker.title,
            SectionsCollectionStub.withSections([]),
            gettext,
        ).match(
            () => {
                expect(replaceContent).toHaveBeenCalledWith([
                    {
                        empty_message: "",
                        footer_message: "",
                        is_loading: false,
                        items: [],
                        label: "",
                    },
                ]);
            },
            (fault) => {
                throw fault;
            },
        );
    });

    it("should mark as loading while the query is performed", () => {
        const query = "lorem ipsum";

        const tracker = TrackerStub.withTitleAndDescription();

        const gettext: Language = {
            $gettext: (msgid: string) => msgid,
            interpolate: (msgid: string) => msgid,
        } as unknown as Language;

        const replaceContent = vi.fn();

        const autocompleter: LazyAutocompleter = {
            replaceContent,
        } as unknown as LazyAutocompleter;

        vi.spyOn(fetch, "getJSON");

        searchExistingArtifactsForAutocompleter(
            query,
            autocompleter,
            tracker,
            tracker.title,
            SectionsCollectionStub.withSections([]),
            gettext,
        );
        expect(replaceContent).toHaveBeenCalledWith([
            {
                label: "Matching artifacts",
                empty_message: "",
                footer_message: "",
                is_loading: true,
                items: [],
            },
        ]);
    });

    it("should display an empty state when no artifacts match the query", () => {
        const query = "lorem ipsum";

        const tracker = TrackerStub.withTitleAndDescription();

        const gettext: Language = {
            $gettext: (msgid: string) => msgid,
            interpolate: (msgid: string) => msgid,
        } as unknown as Language;

        const replaceContent = vi.fn();

        const autocompleter: LazyAutocompleter = {
            replaceContent,
        } as unknown as LazyAutocompleter;

        vi.spyOn(fetch, "getJSON").mockReturnValue(okAsync([]));

        searchExistingArtifactsForAutocompleter(
            query,
            autocompleter,
            tracker,
            tracker.title,
            SectionsCollectionStub.withSections([]),
            gettext,
        ).match(
            () => {
                expect(replaceContent).toHaveBeenCalledWith([
                    {
                        label: "Matching artifacts",
                        empty_message: "No artifact is matching your query",
                        footer_message: "",
                        is_loading: false,
                        items: [],
                    },
                ]);
            },
            (fault) => {
                throw fault;
            },
        );
    });

    it("should display matching artifacts", () => {
        const query = "lorem ipsum";

        const tracker = TrackerStub.withTitleAndDescription();

        const gettext: Language = {
            $gettext: (msgid: string) => msgid,
            interpolate: (msgid: string) => msgid,
        } as unknown as Language;

        const replaceContent = vi.fn();

        const autocompleter: LazyAutocompleter = {
            replaceContent,
        } as unknown as LazyAutocompleter;

        vi.spyOn(fetch, "getJSON").mockReturnValue(
            okAsync([
                {
                    id: 123,
                    title: "Artifact 1",
                    xref: "art #123",
                    tracker: {
                        color_name: "fiesta-red",
                    },
                },
                {
                    id: 124,
                    title: "Artifact 2",
                    xref: "art #124",
                    tracker: {
                        color_name: "fiesta-red",
                    },
                },
            ]),
        );

        searchExistingArtifactsForAutocompleter(
            query,
            autocompleter,
            tracker,
            tracker.title,
            SectionsCollectionStub.withSections([]),
            gettext,
        ).match(
            () => {
                expect(replaceContent).toHaveBeenCalledWith([
                    {
                        label: "Matching artifacts to use as section",
                        empty_message: "No artifact is matching your query",
                        footer_message: "",
                        is_loading: false,
                        items: [
                            {
                                value: {
                                    id: 123,
                                    title: "Artifact 1",
                                    xref: "art #123",
                                    tracker: {
                                        color_name: "fiesta-red",
                                    },
                                },
                                is_disabled: false,
                            },
                            {
                                value: {
                                    id: 124,
                                    title: "Artifact 2",
                                    xref: "art #124",
                                    tracker: {
                                        color_name: "fiesta-red",
                                    },
                                },
                                is_disabled: false,
                            },
                        ],
                    },
                ]);
            },
            (fault) => {
                throw fault;
            },
        );
    });

    it("should separate and disable artifacts that are already present as section in the document", () => {
        const query = "lorem ipsum";

        const tracker = TrackerStub.withTitleAndDescription();

        const gettext: Language = {
            $gettext: (msgid: string) => msgid,
            interpolate: (msgid: string) => msgid,
        } as unknown as Language;

        const replaceContent = vi.fn();

        const autocompleter: LazyAutocompleter = {
            replaceContent,
        } as unknown as LazyAutocompleter;

        vi.spyOn(fetch, "getJSON").mockReturnValue(
            okAsync([
                {
                    id: 123,
                    title: "Artifact 1",
                    xref: "art #123",
                    tracker: {
                        color_name: "fiesta-red",
                    },
                },
                {
                    id: 124,
                    title: "Artifact 2",
                    xref: "art #124",
                    tracker: {
                        color_name: "fiesta-red",
                    },
                },
            ]),
        );

        const section = ArtifactSectionFactory.create();

        const stored_section = CreateStoredSections.fromArtidocSection(
            ArtifactSectionFactory.override({
                ...section,
                artifact: {
                    ...section.artifact,
                    id: 124,
                },
            }),
        );

        searchExistingArtifactsForAutocompleter(
            query,
            autocompleter,
            tracker,
            tracker.title,
            SectionsCollectionStub.withSections([stored_section]),
            gettext,
        ).match(
            () => {
                expect(replaceContent).toHaveBeenCalledWith([
                    {
                        label: "Matching artifacts to use as section",
                        empty_message: "No artifact is matching your query",
                        footer_message: "",
                        is_loading: false,
                        items: [
                            {
                                value: {
                                    id: 123,
                                    title: "Artifact 1",
                                    xref: "art #123",
                                    tracker: {
                                        color_name: "fiesta-red",
                                    },
                                },
                                is_disabled: false,
                            },
                        ],
                    },
                    {
                        label: "Already existing as section in this document",
                        empty_message: "",
                        footer_message: "",
                        is_loading: false,
                        items: [
                            {
                                value: {
                                    id: 124,
                                    title: "Artifact 2",
                                    xref: "art #124",
                                    tracker: {
                                        color_name: "fiesta-red",
                                    },
                                },
                                is_disabled: true,
                            },
                        ],
                    },
                ]);
            },
            (fault) => {
                throw fault;
            },
        );
    });
});
