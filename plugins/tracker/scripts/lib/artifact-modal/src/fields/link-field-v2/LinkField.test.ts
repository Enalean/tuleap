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

import { setCatalog } from "../../gettext-catalog";
import { getFormattedArtifacts, LinkField, retrieveLinkedArtifacts } from "./LinkField";

import * as linked_artifacts_retriever from "./links-retriever";
import * as modal_creation_mode_state from "../../modal-creation-mode-state";

import type { HostElement } from "./LinkField";
import type { LinkedArtifact } from "./links-retriever";

const getDocument = (): Document => document.implementation.createHTMLDocument();

jest.mock("./links-retriever");

function getHost(): HostElement {
    return {
        fieldId: 60,
        label: "Links overview",
        artifactId: 1601,
        linked_artifacts: [],
        has_loaded_content: false,
        is_loading: false,
        error_message: "",
    } as unknown as HostElement;
}

function setCreationMode(is_in_creation_mode: boolean): void {
    jest.spyOn(modal_creation_mode_state, "isInCreationMode").mockReturnValue(is_in_creation_mode);
}

describe("LinkField", () => {
    let parent_artifact: LinkedArtifact;

    beforeEach(() => {
        parent_artifact = {
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
        };

        setCatalog({ getString: (msgid) => msgid });
    });

    describe("retrieveLinkedArtifacts", () => {
        it("When the modal is in creation mode, Then it will not try fetch the linked artifacts", async () => {
            const getLinkedArtifacts = jest.spyOn(linked_artifacts_retriever, "getLinkedArtifacts");
            const host = getHost();

            setCreationMode(true);
            await retrieveLinkedArtifacts(host);

            expect(getLinkedArtifacts).not.toHaveBeenCalled();
            expect(host.linked_artifacts).toEqual([]);
            expect(host.has_loaded_content).toBe(false);
            expect(host.is_loading).toBe(false);
            expect(host.error_message).toEqual("");
        });

        it("When the modal is in edition mode, Then it fetches the linked artifacts and display them", async () => {
            const getLinkedArtifacts = jest.spyOn(linked_artifacts_retriever, "getLinkedArtifacts");
            getLinkedArtifacts.mockResolvedValue([parent_artifact]);

            const host = getHost();

            host.is_loading = true;

            setCreationMode(false);
            await retrieveLinkedArtifacts(host);

            expect(getLinkedArtifacts).toHaveBeenCalledWith(host.artifactId);
            expect(host.linked_artifacts).toEqual([parent_artifact]);
            expect(host.has_loaded_content).toBe(true);
            expect(host.is_loading).toBe(false);
            expect(host.error_message).toEqual("");
        });

        it("When an error occurres, Then it formats the error message and stores it", async () => {
            const getLinkedArtifacts = jest.spyOn(linked_artifacts_retriever, "getLinkedArtifacts");
            getLinkedArtifacts.mockRejectedValue(new Error("Nope"));

            const host = getHost();

            host.is_loading = true;

            setCreationMode(false);
            await retrieveLinkedArtifacts(host);

            expect(getLinkedArtifacts).toHaveBeenCalledWith(host.artifactId);
            expect(host.linked_artifacts).toEqual([]);
            expect(host.has_loaded_content).toBe(true);
            expect(host.is_loading).toBe(false);
            expect(host.error_message).toEqual("Unable to retrieve the linked artifacts: Nope");
        });
    });

    describe("getFormattedArtifacts", () => {
        it("Given a collection of artifact, Then it should return render functions to display them", () => {
            const host = getHost();
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
                    status: "Open",
                },
            ];
            host.linked_artifacts = linked_artifacts;

            getFormattedArtifacts(host).forEach((renderArtifact, index) => {
                const doc = getDocument();
                const target = doc.createElement("div") as unknown as ShadowRoot;
                const artifact_to_render = linked_artifacts[index];

                renderArtifact(host, target);

                const link = target.querySelector("[data-test=artifact-link]");
                const xref = target.querySelector("[data-test=artifact-xref]");
                const title = target.querySelector("[data-test=artifact-title]");
                const status = target.querySelector("[data-test=artifact-status]");

                if (
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
            });
        });
    });

    describe("Display", () => {
        beforeEach(() => {
            setCreationMode(false);
        });

        it("should hide the table when content has loaded and there are no links to display", () => {
            const host = getHost();

            host.has_loaded_content = true;

            const update = LinkField.content(host);
            const doc = getDocument();
            const target = doc.createElement("div") as unknown as ShadowRoot;

            update(host, target);

            const table = target.querySelector("#tuleap-artifact-modal-link-table");
            if (!(table instanceof HTMLElement)) {
                throw new Error("Unable to find #tuleap-artifact-modal-link-table");
            }

            expect(table.classList.contains("tuleap-artifact-modal-link-field-empty")).toBe(true);
        });

        it("should hide the table and show an alert when an error occurred during the retrieval of the linked artifacts", () => {
            const host = getHost();

            host.has_loaded_content = true;
            host.error_message = "Unable to retrieve the linked artifacts because reasons";

            const update = LinkField.content(host);
            const doc = getDocument();
            const target = doc.createElement("div") as unknown as ShadowRoot;

            update(host, target);

            const table = target.querySelector("[data-test=linked-artifacts-table]");
            const error = target.querySelector("[data-test=linked-artifacts-error]");

            if (!(table instanceof HTMLElement) || !(error instanceof HTMLElement)) {
                throw new Error("Unable to find an expected element in DOM");
            }

            expect(table.classList.contains("tuleap-artifact-modal-link-field-empty")).toBe(true);
            expect(error.textContent?.trim()).toBe(host.error_message);
        });
    });
});
