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
import { getFormattedArtifacts, LinkField } from "./LinkField";

import * as linked_artifacts_retriever from "./links-retriever";
import * as modal_creation_mode_state from "../../modal-creation-mode-state";

import type { HostElement } from "./LinkField";

const getDocument = (): Document => document.implementation.createHTMLDocument();

jest.mock("./links-retriever");

function getHost(): HostElement {
    return {
        fieldId: 60,
        label: "Links overview",
        artifactId: 1601,
    } as unknown as HostElement;
}

function setCreationMode(is_in_creation_mode: boolean): void {
    jest.spyOn(modal_creation_mode_state, "isInCreationMode").mockReturnValue(is_in_creation_mode);
}

describe("LinkField", () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    describe("Linked artifacts display", () => {
        it("When the modal is in creation mode, Then it will not try display the linked artifacts", () => {
            const getLinkedArtifacts = jest.spyOn(linked_artifacts_retriever, "getLinkedArtifacts");

            setCreationMode(true);

            const update = LinkField.content(getHost());
            const doc = getDocument();
            const target = doc.createElement("div") as unknown as ShadowRoot;

            update(getHost(), target);

            expect(getLinkedArtifacts).not.toHaveBeenCalled();
            expect(target.querySelector("[data-test=linked-artifacts-list]")).toBeNull();
        });

        it("When the modal is in edition mode, Then it fetches the linked artifacts and display them", () => {
            const getLinkedArtifacts = jest.spyOn(linked_artifacts_retriever, "getLinkedArtifacts");

            setCreationMode(false);

            getLinkedArtifacts.mockResolvedValue([
                {
                    xref: "art #123",
                    title: "A parent",
                    html_url: "/url/to/artifact/123",
                    tracker: {
                        color_name: "red-wine",
                    },
                },
            ]);

            const update = LinkField.content(getHost());
            const doc = getDocument();
            const target = doc.createElement("div") as unknown as ShadowRoot;

            update(getHost(), target);

            expect(getLinkedArtifacts).toHaveBeenCalled();
            expect(target.querySelector("[data-test=linked-artifacts-list]")).not.toBeNull();
        });

        describe("getFormattedArtifacts", () => {
            it("Given a collection of artifact, Then it should return render functions to display them", () => {
                const linked_artifacts = [
                    {
                        xref: "art #123",
                        title: "A parent",
                        html_url: "/url/to/artifact/123",
                        tracker: {
                            color_name: "red-wine",
                        },
                    },
                    {
                        xref: "art #234",
                        title: "A child",
                        html_url: "/url/to/artifact/234",
                        tracker: {
                            color_name: "surf-green",
                        },
                    },
                ];

                getFormattedArtifacts(linked_artifacts).forEach((renderArtifact, index) => {
                    const doc = getDocument();
                    const target = doc.createElement("div") as unknown as ShadowRoot;
                    const artifact_to_render = linked_artifacts[index];

                    renderArtifact(getHost(), target);

                    const link = target.querySelector("[data-test=artifact-link]");
                    const xref = target.querySelector("[data-test=artifact-xref]");
                    const title = target.querySelector("[data-test=artifact-title]");

                    if (
                        !(link instanceof HTMLAnchorElement) ||
                        !(xref instanceof HTMLElement) ||
                        !(title instanceof HTMLElement)
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
                });
            });
        });
    });
});
