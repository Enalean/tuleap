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

import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement } from "./LinkField";
import {
    getEmptyStateIfNeeded,
    getFormattedArtifacts,
    getSkeletonIfNeeded,
    LinkField,
} from "./LinkField";
import {
    buildForCreationMode,
    buildFromArtifacts,
    buildFromError,
    buildLoadingState,
} from "./LinkFieldPresenter";

const getDocument = (): Document => document.implementation.createHTMLDocument();

function getHost(data?: Partial<LinkField>): HostElement {
    return {
        fieldId: 60,
        label: "Links overview",
        ...data,
    } as unknown as HostElement;
}

describe("LinkField", () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    describe("getFormattedArtifacts", () => {
        it("Given a collection of artifacts, Then it should return render functions to display them", () => {
            const linked_artifacts = [
                {
                    xref: "art #123",
                    title: "A parent",
                    html_url: "/url/to/artifact/123",
                    tracker: {
                        color_name: "red-wine",
                    },
                    link_type: {
                        shortname: "_is_child",
                        direction: "reverse",
                        label: "Parent",
                    },
                    status: "Open",
                    is_open: true,
                },
                {
                    xref: "art #234",
                    title: "A child",
                    html_url: "/url/to/artifact/234",
                    tracker: {
                        color_name: "surf-green",
                    },
                    link_type: {
                        shortname: "_is_child",
                        direction: "forward",
                        label: "Child",
                    },
                    status: "Closed",
                    is_open: false,
                },
            ];
            const presenter = buildFromArtifacts(linked_artifacts);
            const host = getHost();
            const doc = getDocument();

            getFormattedArtifacts(presenter).forEach((renderArtifact, index) => {
                const target = doc.createElement("div") as unknown as ShadowRoot;
                const artifact_to_render = linked_artifacts[index];

                renderArtifact(host, target);

                const row = target.querySelector("[data-test=artifact-row]");
                const link = target.querySelector("[data-test=artifact-link]");
                const xref = target.querySelector("[data-test=artifact-xref]");
                const title = target.querySelector("[data-test=artifact-title]");
                const status = target.querySelector("[data-test=artifact-status]");

                if (
                    !(row instanceof HTMLElement) ||
                    !(link instanceof HTMLAnchorElement) ||
                    !(xref instanceof HTMLElement) ||
                    !(title instanceof HTMLElement) ||
                    !(status instanceof HTMLElement)
                ) {
                    throw new Error("An expected element has not been found in template");
                }

                expect(link.href).toEqual(artifact_to_render.html_url);
                expect(
                    xref.classList.contains(
                        `cross-ref-badge-${artifact_to_render.tracker.color_name}`
                    )
                ).toBe(true);
                expect(xref.textContent?.trim()).toEqual(artifact_to_render.xref);
                expect(title.textContent?.trim()).toEqual(artifact_to_render.title);
                expect(status.textContent?.trim()).toEqual(artifact_to_render.status);

                expect(row.classList.contains("link-field-table-row-muted")).toBe(
                    !artifact_to_render.is_open
                );
                expect(status.classList.contains("tlp-badge-secondary")).toBe(
                    !artifact_to_render.is_open
                );
                expect(status.classList.contains("tlp-badge-success")).toBe(
                    artifact_to_render.is_open
                );
            });
        });
    });

    describe("Display", () => {
        let target: ShadowRoot;
        beforeEach(() => {
            target = getDocument().createElement("div") as unknown as ShadowRoot;
        });

        it("should hide the table and show an alert when an error occurred during the retrieval of the linked artifacts", () => {
            const error_message = "Unable to retrieve the linked artifacts because reasons";
            const presenter = buildFromError(new Error(error_message));
            const host = getHost({ presenter });
            const update = LinkField.content(host);
            update(host, target);

            const table = target.querySelector("[data-test=linked-artifacts-table]");
            const error = target.querySelector("[data-test=linked-artifacts-error]");

            if (!(table instanceof HTMLElement) || !(error instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(table.classList.contains("tuleap-artifact-modal-link-field-empty")).toBe(true);
            expect(error.textContent?.trim()).toContain(error_message);
        });

        it("should render a skeleton row when the links are being loaded", () => {
            const render = getSkeletonIfNeeded(buildLoadingState());

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-field-table-skeleton]")).not.toBe(null);
        });

        it("should render an empty state row when content has been loaded and there is no link to display", () => {
            const render = getEmptyStateIfNeeded(buildForCreationMode());

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-table-empty-state]")).not.toBe(null);
        });
    });
});
