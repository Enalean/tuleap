/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { LinkedArtifactIdentifierStub } from "../../../../../tests/stubs/LinkedArtifactIdentifierStub";
import type { HostElement } from "./LinkField";
import { getLinkedArtifactTemplate, getActionButton } from "./LinkedArtifactTemplate";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { setCatalog } from "../../../../gettext-catalog";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { LinkFieldController } from "./LinkFieldController";
import { RetrieveAllLinkedArtifactsStub } from "../../../../../tests/stubs/RetrieveAllLinkedArtifactsStub";
import { RetrieveLinkedArtifactsSyncStub } from "../../../../../tests/stubs/RetrieveLinkedArtifactsSyncStub";
import { AddLinkMarkedForRemovalStub } from "../../../../../tests/stubs/AddLinkMarkedForRemovalStub";
import { DeleteLinkMarkedForRemovalStub } from "../../../../../tests/stubs/DeleteLinkMarkedForRemovalStub";
import { VerifyLinkIsMarkedForRemovalStub } from "../../../../../tests/stubs/VerifyLinkIsMarkedForRemovalStub";
import { CurrentArtifactIdentifierStub } from "../../../../../tests/stubs/CurrentArtifactIdentifierStub";
import type { VerifyLinkIsMarkedForRemoval } from "../../../../domain/fields/link-field-v2/VerifyLinkIsMarkedForRemoval";
import type { LinkedArtifact } from "../../../../domain/fields/link-field-v2/LinkedArtifact";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";

describe(`LinkedArtifactTemplate`, () => {
    let target: ShadowRoot;
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
        target = document.implementation
            .createHTMLDocument()
            .createElement("div") as unknown as ShadowRoot;
    });

    const render = (linked_artifact_presenter: LinkedArtifactPresenter): void => {
        const host = {} as HostElement;

        const updateFunction = getLinkedArtifactTemplate(linked_artifact_presenter);
        updateFunction(host, target);
    };

    it.each([
        [
            LinkedArtifactPresenter.fromLinkedArtifact(
                LinkedArtifactStub.withDefaults({
                    identifier: LinkedArtifactIdentifierStub.withId(123),
                    title: "A parent",
                    xref: "art #123",
                    uri: "/url/to/artifact/123",
                    status: "Open",
                    is_open: true,
                    tracker: { color_name: "red-wine" },
                    link_type: {
                        shortname: "_is_child",
                        direction: "reverse",
                        label: "Parent",
                    },
                }),
                false
            ),
        ],
        [
            LinkedArtifactPresenter.fromLinkedArtifact(
                LinkedArtifactStub.withDefaults({
                    identifier: LinkedArtifactIdentifierStub.withId(234),
                    title: "A child",
                    xref: "art #234",
                    uri: "/url/to/artifact/234",
                    status: "Closed",
                    is_open: false,
                    tracker: { color_name: "surf-green" },
                    link_type: {
                        shortname: "",
                        direction: "forward",
                        label: "",
                    },
                }),
                true
            ),
        ],
    ])(`will render a linked artifact`, (presenter) => {
        render(presenter);

        const row = target.querySelector("[data-test=artifact-row]");
        const link = target.querySelector("[data-test=artifact-link]");
        const xref = target.querySelector("[data-test=artifact-xref]");
        const title = target.querySelector("[data-test=artifact-title]");
        const status = target.querySelector("[data-test=artifact-status]");
        const type = target.querySelector("[data-test=artifact-link-type]");
        const expected_type =
            presenter.link_type.shortname === "" ? "Linked to" : presenter.link_type.label;

        if (
            !(row instanceof HTMLElement) ||
            !(link instanceof HTMLAnchorElement) ||
            !(xref instanceof HTMLElement) ||
            !(title instanceof HTMLElement) ||
            !(status instanceof HTMLElement) ||
            !(type instanceof HTMLElement)
        ) {
            throw new Error("An expected element has not been found in template");
        }

        expect(link.href).toBe(presenter.uri);
        expect(xref.classList.contains(`cross-ref-badge-${presenter.tracker.color_name}`)).toBe(
            true
        );
        expect(xref.textContent?.trim()).toBe(presenter.xref);
        expect(title.textContent?.trim()).toBe(presenter.title);
        expect(status.textContent?.trim()).toBe(presenter.status);
        expect(type.textContent?.trim()).toBe(expected_type);

        expect(row.classList.contains("link-field-table-row-muted")).toBe(!presenter.is_open);
        expect(status.classList.contains("tlp-badge-secondary")).toBe(!presenter.is_open);
        expect(status.classList.contains("tlp-badge-success")).toBe(presenter.is_open);
    });

    describe(`getActionButton`, () => {
        let marked_for_removal_verifier: VerifyLinkIsMarkedForRemoval;

        beforeEach(() => {
            marked_for_removal_verifier =
                VerifyLinkIsMarkedForRemovalStub.withAllLinksMarkedForRemoval();
        });

        const getHost = (linked_artifact: LinkedArtifact): HostElement => {
            const controller = LinkFieldController(
                RetrieveAllLinkedArtifactsStub.withoutLink(),
                RetrieveLinkedArtifactsSyncStub.withLinkedArtifacts(linked_artifact),
                AddLinkMarkedForRemovalStub.withCount(),
                DeleteLinkMarkedForRemovalStub.withCount(),
                marked_for_removal_verifier,
                CurrentArtifactIdentifierStub.withId(72)
            );

            return {
                presenter: LinkFieldPresenter.buildLoadingState(),
                controller,
            } as unknown as HostElement;
        };

        const render = (
            host: HostElement,
            linked_artifact: LinkedArtifact,
            is_marked_for_removal: boolean
        ): void => {
            const linked_artifact_presenter = LinkedArtifactPresenter.fromLinkedArtifact(
                linked_artifact,
                is_marked_for_removal
            );
            const update = getActionButton(linked_artifact_presenter);
            update(host, target);
        };

        it(`will mark the artifact for removal`, () => {
            const linked_artifact = LinkedArtifactStub.withDefaults();
            const host = getHost(linked_artifact);
            render(host, linked_artifact, false);
            const button = target.querySelector("[data-test=action-button]");

            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("An expected element has not been found in template");
            }
            button.click();

            expect(
                host.presenter.linked_artifacts.some((artifact) => artifact.is_marked_for_removal)
            ).toBe(true);
        });

        it(`will cancel the removal of the artifact`, () => {
            marked_for_removal_verifier =
                VerifyLinkIsMarkedForRemovalStub.withNoLinkMarkedForRemoval();
            const linked_artifact = LinkedArtifactStub.withDefaults();
            const host = getHost(linked_artifact);
            render(host, linked_artifact, true);
            const button = target.querySelector("[data-test=action-button]");

            if (!(button instanceof HTMLButtonElement)) {
                throw new Error("An expected element has not been found in template");
            }
            button.click();

            expect(
                host.presenter.linked_artifacts.some((artifact) => artifact.is_marked_for_removal)
            ).toBe(false);
        });

        it(`will not render a button if the link's direction is "reverse"`, () => {
            const linked_artifact = LinkedArtifactStub.withLinkType(
                LinkTypeStub.buildParentLinkType()
            );
            const host = getHost(linked_artifact);
            render(host, linked_artifact, false);

            const button = target.querySelector("[data-test=action-button]");

            expect(button).toBeNull();
        });
    });
});
