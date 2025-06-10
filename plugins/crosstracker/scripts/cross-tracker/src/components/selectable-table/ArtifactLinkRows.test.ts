/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { okAsync } from "neverthrow";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import { ArtifactRowBuilder } from "../../../tests/builders/ArtifactRowBuilder";
import { ArtifactsTableBuilder as ArtifactsTableBuilderForTests } from "../../../tests/builders/ArtifactsTableBuilder";
import { NUMERIC_CELL, PRETTY_TITLE_CELL } from "../../domain/ArtifactsTable";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import type { ColumnName } from "../../domain/ColumnName";
import { PRETTY_TITLE_COLUMN_NAME } from "../../domain/ColumnName";
import type { ArtifactsTableWithTotal } from "../../domain/RetrieveArtifactsTable";
import type { RetrieveArtifactLinks } from "../../domain/RetrieveArtifactLinks";
import { RETRIEVE_ARTIFACT_LINKS } from "../../injection-symbols";
import ArtifactLinkRows from "./ArtifactLinkRows.vue";
import EditCell from "./EditCell.vue";
import ArtifactLinkRowSkeleton from "./skeleton/ArtifactLinkRowSkeleton.vue";

const RetrieveArtifactLinksTableStub = {
    withContent(forward_links: ArtifactsTableWithTotal): RetrieveArtifactLinks {
        return {
            getForwardLinks: () => okAsync(forward_links),
        };
    },

    withDefaultContent(): RetrieveArtifactLinks {
        return {
            getForwardLinks: () => okAsync(new ArtifactsTableBuilderForTests().buildWithTotal(0)),
        };
    },
};

vi.useFakeTimers();

const NUMERIC_COLUMN_NAME = "remaining_effort";

describe("ArtifactLinkRows", () => {
    let number_of_forward_link: number,
        number_of_reverse_link: number,
        artifact_links_table_retriever: RetrieveArtifactLinks;

    beforeEach(() => {
        number_of_forward_link = 0;
        number_of_reverse_link = 0;
        artifact_links_table_retriever = RetrieveArtifactLinksTableStub.withDefaultContent();
    });

    function getWrapper(): VueWrapper {
        return shallowMount(ArtifactLinkRows, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [RETRIEVE_ARTIFACT_LINKS.valueOf()]: artifact_links_table_retriever,
                },
            },
            props: {
                artifact_id: 477,
                query_id: "0196d46b-aa17-7249-816a-b23604f5721a",
                row: new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .buildWithNumberOfLinks(number_of_forward_link, number_of_reverse_link),
                columns: new Set<ColumnName>().add(PRETTY_TITLE_COLUMN_NAME),
            },
        });
    }

    it("should display skeletons components for forward and reverse links", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(2);
        expect(skeletons[0].props("link_type")).toBe("forward");
        expect(skeletons[1].props("link_type")).toBe("reverse");
    });

    it("should only display forward skeleton component, when there is no reverse links", () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 0;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(1);
        expect(skeletons[0].props("link_type")).toBe("forward");
    });

    it("should only display reverse skeleton component, when there is no forward links", () => {
        number_of_forward_link = 0;
        number_of_reverse_link = 1;
        const wrapper = getWrapper();

        const skeletons = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons.length).toBe(1);
        expect(skeletons[0].props("link_type")).toBe("reverse");
    });

    it("should display 2 forward links and one skeleton reverse link", async () => {
        number_of_forward_link = 2;
        number_of_reverse_link = 1;

        const table = new ArtifactsTableBuilderForTests()
            .withColumn(PRETTY_TITLE_COLUMN_NAME)
            .withColumn(NUMERIC_COLUMN_NAME)
            .withArtifactRow(
                new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(NUMERIC_COLUMN_NAME, {
                        type: NUMERIC_CELL,
                        value: Option.fromValue(74),
                    })
                    .build(),
            )
            .withArtifactRow(
                new ArtifactRowBuilder()
                    .addCell(PRETTY_TITLE_COLUMN_NAME, {
                        type: PRETTY_TITLE_CELL,
                        title: "earthmaking",
                        tracker_name: "lifesome",
                        artifact_id: 512,
                        color: "inca-silver",
                    })
                    .addCell(NUMERIC_COLUMN_NAME, {
                        type: NUMERIC_CELL,
                        value: Option.fromValue(74),
                    })
                    .build(),
            )
            .buildWithTotal(2);

        artifact_links_table_retriever = RetrieveArtifactLinksTableStub.withContent(table);

        const wrapper = getWrapper();
        const skeletons_before_call = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons_before_call.length).toBe(2);
        expect(skeletons_before_call[0].props("link_type")).toBe("forward");
        expect(skeletons_before_call[1].props("link_type")).toBe("reverse");

        await vi.runOnlyPendingTimersAsync();

        const skeletons_after_call = wrapper.findAllComponents(ArtifactLinkRowSkeleton);

        expect(skeletons_after_call.length).toBe(1);
        expect(skeletons_after_call[0].props("link_type")).toBe("reverse");

        expect(wrapper.findAllComponents(EditCell)).toHaveLength(2);
    });
});
