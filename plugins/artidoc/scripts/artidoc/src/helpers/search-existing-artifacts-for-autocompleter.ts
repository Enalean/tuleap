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

import type { GroupOfItems, LazyAutocompleter, LazyboxItem } from "@tuleap/lazybox";
import type { TitleFieldDefinition, Tracker } from "@/configuration/AllowedTrackersCollection";
import { getJSON, uri } from "@tuleap/fetch-result";
import { isArtifactSection } from "@/helpers/artidoc-section.type";
import type { Language } from "vue3-gettext";
import type { ColorName } from "@tuleap/core-constants";
import type {
    ReactiveStoredArtidocSection,
    SectionsCollection,
} from "@/sections/SectionsCollection";
import type { ResultAsync } from "neverthrow";
import { errAsync, okAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";

export interface Artifact {
    readonly id: number;
    readonly xref: string;
    readonly tracker: {
        readonly color_name: ColorName;
    };
    readonly title: string;
}

export function isArtifact(value: unknown): value is Artifact {
    return typeof value === "object" && value !== null && "id" in value;
}

interface ArtifactLazyboxItem extends LazyboxItem {
    value: Artifact;
}

const items: GroupOfItems = {
    label: "",
    empty_message: "",
    footer_message: "",
    is_loading: false,
    items: [],
};

export function searchExistingArtifactsForAutocompleter(
    query: string,
    autocompleter: LazyAutocompleter,
    tracker: Tracker,
    title_field: TitleFieldDefinition,
    sections_collection: SectionsCollection,
    gettext_provider: Language,
): ResultAsync<boolean, Fault> {
    const { $gettext, interpolate } = gettext_provider;
    if (query === "") {
        autocompleter.replaceContent([items]);
        return okAsync(true);
    }

    autocompleter.replaceContent([
        {
            ...items,
            label: $gettext("Matching artifacts"),
            is_loading: true,
        },
    ]);

    const q: Record<number, string> = {};
    q[title_field.field_id] = query;

    return getJSON<Artifact[]>(
        uri`/api/trackers/${tracker.id}/artifacts?query=${JSON.stringify(q)}`,
    )
        .andThen((artifacts: Artifact[]) => {
            if (artifacts.length === 0) {
                autocompleter.replaceContent([
                    {
                        ...items,
                        label: $gettext("Matching artifacts"),
                        empty_message: $gettext("No artifact is matching your query"),
                    },
                ]);
                return okAsync(true);
            }

            interface Partition {
                matching: ArtifactLazyboxItem[];
                already_there: ArtifactLazyboxItem[];
            }

            const partition: Partition = artifacts.reduce(
                (partition: Partition, artifact: Artifact): Partition => {
                    if (
                        sections_collection.sections.value.some(
                            (section: ReactiveStoredArtidocSection) =>
                                isArtifactSection(section.value) &&
                                section.value.artifact.id === artifact.id,
                        )
                    ) {
                        partition.already_there.push({
                            value: artifact,
                            is_disabled: true,
                        });
                    } else {
                        partition.matching.push({
                            value: artifact,
                            is_disabled: false,
                        });
                    }

                    return partition;
                },
                { matching: [], already_there: [] },
            );

            autocompleter.replaceContent([
                {
                    ...items,
                    label: $gettext("Matching artifacts to use as section"),
                    empty_message: $gettext("No artifact is matching your query"),
                    items: partition.matching,
                },
                ...(partition.already_there.length > 0
                    ? [
                          {
                              ...items,
                              label: $gettext("Already existing as section in this document"),
                              items: partition.already_there,
                          },
                      ]
                    : []),
            ]);

            return okAsync(true);
        })
        .orElse((fault) => {
            autocompleter.replaceContent([items]);

            return errAsync(
                Fault.fromMessage(
                    interpolate(
                        $gettext("An error occurred while searching for artifacts: %{ details }"),
                        {
                            details: String(fault),
                        },
                    ),
                ),
            );
        });
}
