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
import type { HostElement, LinkField } from "./LinkField";
import {
    getEmptyStateIfNeeded,
    getLinkFieldCanOnlyHaveOneParentNote,
    getSkeletonIfNeeded,
    setAllowedTypes,
    setCurrentLinkType,
    setLinkedArtifacts,
    setNewLinks,
} from "./LinkField";
import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import type { NewLink } from "../../../../domain/fields/link-field-v2/NewLink";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import type { LinkType } from "../../../../domain/fields/link-field-v2/LinkType";
import { LinkSelectorStub } from "../../../../../tests/stubs/LinkSelectorStub";
import type { LinkSelector } from "@tuleap/link-selector";
import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants/src/constants";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import type { LinkFieldControllerType } from "./LinkFieldController";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";

describe("LinkField", () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    describe("Display", () => {
        let target: ShadowRoot, artifact_cross_reference: ArtifactCrossReference | null;

        function getHost(): HostElement {
            return {
                field_presenter: LinkFieldPresenter.fromFieldAndCrossReference(
                    {
                        field_id: 60,
                        type: "art_link",
                        label: "Links overview",
                        allowed_types: [],
                    },
                    artifact_cross_reference
                ),
            } as unknown as HostElement;
        }

        beforeEach(() => {
            artifact_cross_reference = null;
            target = document.implementation
                .createHTMLDocument()
                .createElement("div") as unknown as ShadowRoot;
        });

        it("should render a skeleton row when the links are being loaded", () => {
            const render = getSkeletonIfNeeded(
                LinkedArtifactCollectionPresenter.buildLoadingState()
            );

            render(getHost(), target);
            expect(target.querySelector("[data-test=link-field-table-skeleton]")).not.toBeNull();
        });

        describe(`emptyState`, () => {
            let linked_artifacts: readonly LinkedArtifactPresenter[], new_links: readonly NewLink[];

            beforeEach(() => {
                linked_artifacts = [];
                new_links = [];
            });

            const renderField = (): void => {
                const host = {
                    linked_artifacts_presenter:
                        LinkedArtifactCollectionPresenter.fromArtifacts(linked_artifacts),
                    new_links_presenter: NewLinkCollectionPresenter.fromLinks(new_links),
                } as LinkField;
                const render = getEmptyStateIfNeeded(host);

                render(getHost(), target);
            };

            it("should render an empty state row when content has been loaded and there is no link to display", () => {
                renderField();
                expect(target.querySelector("[data-test=link-table-empty-state]")).not.toBeNull();
            });

            it(`does not show an empty state when there is at least one linked artifact`, () => {
                linked_artifacts = [
                    LinkedArtifactPresenter.fromLinkedArtifact(
                        LinkedArtifactStub.withDefaults(),
                        false
                    ),
                ];
                renderField();
                expect(target.querySelector("[data-test=link-table-empty-state]")).toBeNull();
            });

            it(`does not show an empty state when there is at least one new link`, () => {
                new_links = [NewLinkStub.withDefaults()];
                renderField();
                expect(target.querySelector("[data-test=link-table-empty-state]")).toBeNull();
            });
        });

        describe("getLinkFieldCanOnlyHaveOneParentNote", () => {
            it("When the modal is open in creation mode, Then it defaults to a generic note", () => {
                const host = getHost();
                const renderNote = getLinkFieldCanOnlyHaveOneParentNote(host);

                renderNote(host, target);

                expect(target.textContent?.trim()).toBe(
                    "Note: an artifact can only have one parent."
                );
            });

            it("When the modal is open in edition mode, Then the note displays the artifact reference in a badge", () => {
                artifact_cross_reference = ArtifactCrossReferenceStub.withRefAndColor(
                    "story #123",
                    "red-wine"
                );

                const host = getHost();
                const renderNote = getLinkFieldCanOnlyHaveOneParentNote(host);

                renderNote(host, target);

                const badge = target.querySelector("[data-test=artifact-cross-ref-badge]");
                if (!badge) {
                    throw new Error("The note should have a cross reference badge, none found.");
                }

                expect(badge.textContent?.trim()).toBe("story #123");
                expect(badge.classList).toContain("tlp-swatch-red-wine");
            });
        });
    });

    describe(`setters`, () => {
        describe(`current_link_type`, () => {
            let link_selector: LinkSelectorStub;

            beforeEach(() => {
                link_selector = LinkSelectorStub.withResetSelectionCallCount();
            });

            const setType = (
                current_link_type: LinkType | undefined,
                new_link_type: LinkType | undefined
            ): LinkType => {
                const host = {
                    link_selector: link_selector as LinkSelector,
                    current_link_type,
                } as LinkField;
                return setCurrentLinkType(host, new_link_type);
            };

            it(`defaults to Untyped link`, () => {
                const link_type = setType(undefined, undefined);

                expect(link_type.shortname).toBe(UNTYPED_LINK);
            });

            it(`Given that the current link type is a custom type,
                When the type is changed to reverse _is_child (Parent),
                Then the selection is reset`, () => {
                const current_link_type = LinkTypeStub.buildReverseCustom();
                const link_type = LinkTypeStub.buildParentLinkType();
                const result = setType(current_link_type, link_type);
                expect(result).toBe(link_type);
                expect(link_selector.getResetCallCount()).toBe(1);
            });

            it(`Given that the current link type is reverse _is_child (Parent),
                When the type is changed to another type,
                Then the selection is reset`, () => {
                const current_link_type = LinkTypeStub.buildParentLinkType();
                const link_type = LinkTypeStub.buildReverseCustom();
                const result = setType(current_link_type, link_type);
                expect(result).toBe(link_type);
                expect(link_selector.getResetCallCount()).toBe(1);
            });

            it(`Given that the current link type is NOT reverse _is_child (Parent),
                When the new type is NOT reverse _is_child (Parent),
                Then the selection will not be reset`, () => {
                const current_link_type = LinkTypeStub.buildUntyped();
                const link_type = LinkTypeStub.buildReverseCustom();
                const result = setType(current_link_type, link_type);
                expect(result).toBe(link_type);
                expect(link_selector.getResetCallCount()).toBe(0);
            });
        });

        describe(`allowed_link_types`, () => {
            let host: LinkField;

            beforeEach(() => {
                host = {
                    controller: {
                        setSelectedLinkType: (
                            link_selector: LinkSelector,
                            type: LinkType
                        ): LinkType => {
                            return type;
                        },
                    } as LinkFieldControllerType,
                } as LinkField;
            });

            const setTypes = (
                presenter: CollectionOfAllowedLinksTypesPresenters | undefined
            ): CollectionOfAllowedLinksTypesPresenters => {
                return setAllowedTypes(host, presenter);
            };

            it("defaults to a en empty CollectionOfAllowedLinksTypesPresenters", () => {
                const allowed_types = setTypes(undefined);
                expect(allowed_types.types).toHaveLength(0);
                expect(allowed_types.is_parent_type_disabled).toBe(false);
            });

            it("When the current link is a reverse child and the parent link type is disabled, then it should default to Untyped link", () => {
                host.current_link_type = LinkTypeStub.buildParentLinkType();
                setTypes(
                    CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                        VerifyHasParentLinkStub.withParentLink(),
                        [
                            {
                                shortname: IS_CHILD_LINK_TYPE,
                                forward_label: "Child",
                                reverse_label: "Parent",
                            },
                        ]
                    )
                );

                expect(host.current_link_type.shortname).toBe(UNTYPED_LINK);
            });
        });

        describe("-", () => {
            let host: LinkField, link_selector: LinkSelectorStub;

            beforeEach(() => {
                link_selector = LinkSelectorStub.withResetSelectionCallCount();
                host = {
                    artifact_link_select: document.implementation
                        .createHTMLDocument()
                        .createElement("select"),
                    link_selector: link_selector as LinkSelector,
                    controller: {
                        displayAllowedTypes: (): CollectionOfAllowedLinksTypesPresenters => {
                            return CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                                VerifyHasParentLinkStub.withNoParentLink(),
                                [
                                    {
                                        shortname: IS_CHILD_LINK_TYPE,
                                        forward_label: "Child",
                                        reverse_label: "Parent",
                                    },
                                ]
                            );
                        },
                    } as LinkFieldControllerType,
                } as LinkField;
            });

            describe("setLinkedArtifacts", () => {
                it("should default to a loading state when there are no linked artifacts yet", () => {
                    const linked_artifacts = setLinkedArtifacts(host, undefined);

                    expect(linked_artifacts.is_loading).toBe(true);
                    expect(linked_artifacts.has_loaded_content).toBe(false);
                    expect(linked_artifacts.linked_artifacts).toHaveLength(0);
                });

                it("should display allowed types when linked artifacts have been retrieved", () => {
                    setLinkedArtifacts(host, LinkedArtifactCollectionPresenter.fromArtifacts([]));

                    expect(host.allowed_link_types.is_parent_type_disabled).toBe(false);
                    expect(host.allowed_link_types.types).not.toHaveLength(0);
                });
            });

            describe("setNewLinks", () => {
                it("defaults to an empty NewLinkCollectionPresenter", () => {
                    const new_links = setNewLinks(host, undefined);
                    expect(new_links).toHaveLength(0);
                });

                it(`should display allowed types when new links have been edited,
                    clear the link-selector selection,
                    and focus the <select> element once done`, () => {
                    jest.spyOn(host.artifact_link_select, "focus");

                    setNewLinks(host, NewLinkCollectionPresenter.fromLinks([]));

                    expect(host.allowed_link_types.is_parent_type_disabled).toBe(false);
                    expect(host.allowed_link_types.types).not.toHaveLength(0);
                    expect(link_selector.getResetCallCount()).toBe(1);
                    expect(host.artifact_link_select.focus).toHaveBeenCalledTimes(1);
                });
            });
        });
    });
});
