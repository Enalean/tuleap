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
    current_link_type_descriptor,
    setLinkedArtifacts,
    setNewLinks,
    setMatchingArtifactSection,
    setRecentlyViewedArtifact,
    setPossibleParentsSection,
} from "./LinkField";
import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import type { NewLink } from "../../../../domain/fields/link-field/NewLink";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import type { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { LinkSelectorStub } from "../../../../../tests/stubs/LinkSelectorStub";
import type { LinkSelector, GroupCollection, GroupOfItems } from "@tuleap/link-selector";
import { UNTYPED_LINK } from "@tuleap/plugin-tracker-constants";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { IS_CHILD_LINK_TYPE } from "@tuleap/plugin-tracker-constants";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import type { LinkFieldControllerType } from "./LinkFieldController";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { selectOrThrow } from "@tuleap/dom";
import { AllowedLinksTypesCollection } from "./AllowedLinksTypesCollection";

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

                const badge = selectOrThrow(target, "[data-test=artifact-cross-ref-badge]");
                expect(badge.textContent?.trim()).toBe("story #123");
                expect(badge.classList).toContain("tlp-swatch-red-wine");
            });
        });
    });

    describe(`setters`, () => {
        describe("dropdown sections setter", () => {
            let link_selector: LinkSelectorStub, host: LinkField;
            beforeEach(() => {
                link_selector = LinkSelectorStub.build();

                const initial_dropdown_content: GroupCollection = [];
                host = {
                    controller: {
                        autoComplete(): void {
                            //Do nothing
                        },
                    } as unknown as LinkFieldControllerType,
                    link_selector: link_selector as LinkSelector,
                    current_link_type: LinkTypeStub.buildUntyped(),
                    matching_artifact_section: initial_dropdown_content,
                    possible_parents_section: initial_dropdown_content,
                    recently_viewed_section: initial_dropdown_content,
                } as LinkField;
            });
            describe("set_matching_artifact_section", () => {
                it("returns an empty array if there is no collection is provided", () => {
                    const result = setMatchingArtifactSection(host, undefined);
                    expect(result).toStrictEqual([]);
                });

                it("returns set the dropdown content and return the collection given", () => {
                    const result = setMatchingArtifactSection(host, [
                        { label: "group 1" } as GroupOfItems,
                    ]);

                    expect(result).toHaveLength(1);
                    expect(result[0].label).toBe("group 1");
                });
            });
            describe("set_recently_viewed_artifact", () => {
                it("returns an empty array if there is no collection is provided", () => {
                    const result = setRecentlyViewedArtifact(host, undefined);
                    expect(result).toStrictEqual([]);
                });

                it("returns set the dropdown content and return the collection given", () => {
                    const result = setRecentlyViewedArtifact(host, [
                        { label: "group 1" } as GroupOfItems,
                    ]);

                    expect(result).toHaveLength(1);
                    expect(result[0].label).toBe("group 1");
                });
            });
            describe("set_possible_parents_section", () => {
                it("returns an empty array if there is no collection is provided", () => {
                    const result = setPossibleParentsSection(host, undefined);
                    expect(result).toStrictEqual([]);
                });

                it("returns set the dropdown content and return the collection given", () => {
                    const result = setPossibleParentsSection(host, [
                        { label: "group 1" } as GroupOfItems,
                    ]);

                    expect(result).toHaveLength(1);
                    expect(result[0].label).toBe("group 1");
                });
            });
        });
        describe(`current_link_type_descriptor`, () => {
            let link_selector: LinkSelectorStub, host: LinkField;

            beforeEach(() => {
                link_selector = LinkSelectorStub.build();

                const initial_dropdown_content: GroupCollection = [];
                host = {
                    controller: {
                        autoComplete(): void {
                            //Do nothing
                        },
                    } as unknown as LinkFieldControllerType,
                    link_selector: link_selector as LinkSelector,
                    current_link_type: LinkTypeStub.buildUntyped(),
                    matching_artifact_section: initial_dropdown_content,
                    possible_parents_section: initial_dropdown_content,
                    recently_viewed_section: initial_dropdown_content,
                } as LinkField;
            });

            const setType = (new_link_type: LinkType | undefined): LinkType => {
                return current_link_type_descriptor.set(host, new_link_type);
            };

            it(`defaults to Untyped link`, () => {
                const link_type = setType(undefined);

                expect(link_type.shortname).toBe(UNTYPED_LINK);
            });

            it(`when the type is changed to reverse _is_child (Parent),
                it will set a special placeholder in link selector`, () => {
                host.current_link_type = LinkTypeStub.buildReverseCustom();
                const setPlaceholder = jest.spyOn(link_selector, "setPlaceholder");

                const link_type = LinkTypeStub.buildParentLinkType();
                const result = setType(link_type);
                expect(result).toBe(link_type);
                expect(setPlaceholder).toHaveBeenCalled();
            });

            it(`when the type is changed to another type,
                it will set the default placeholder in link selector`, () => {
                const setPlaceholder = jest.spyOn(link_selector, "setPlaceholder");

                const link_type = setType(LinkTypeStub.buildUntyped());
                const result = setType(link_type);
                expect(result).toBe(link_type);
                expect(setPlaceholder).toHaveBeenCalled();
            });

            it(`when the current type is changed,
                it will call the autocompleter with an empty string`, () => {
                const autoComplete = jest.spyOn(host.controller, "autoComplete");

                current_link_type_descriptor.observe(host);

                expect(autoComplete).toHaveBeenCalledWith(host, "");
            });
        });

        describe(`setAllowedTypes()`, () => {
            let host: LinkField;

            beforeEach(() => {
                host = {
                    current_link_type: LinkTypeStub.buildUntyped(),
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

            it(`When the current link is a reverse child and the parent link type is disabled,
                then it should default to Untyped link`, () => {
                host.current_link_type = LinkTypeStub.buildParentLinkType();
                setTypes(
                    CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                        VerifyHasParentLinkStub.withParentLink(),
                        AllowedLinksTypesCollection.buildFromTypesRepresentations([
                            {
                                shortname: IS_CHILD_LINK_TYPE,
                                forward_label: "Child",
                                reverse_label: "Parent",
                            },
                        ])
                    )
                );

                expect(host.current_link_type.shortname).toBe(UNTYPED_LINK);
            });
        });

        describe("-", () => {
            let host: LinkField;

            beforeEach(() => {
                host = {
                    artifact_link_select: document.implementation
                        .createHTMLDocument()
                        .createElement("select"),
                    controller: {
                        displayAllowedTypes: (): CollectionOfAllowedLinksTypesPresenters => {
                            return CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                                VerifyHasParentLinkStub.withNoParentLink(),
                                AllowedLinksTypesCollection.buildFromTypesRepresentations([
                                    {
                                        shortname: IS_CHILD_LINK_TYPE,
                                        forward_label: "Child",
                                        reverse_label: "Parent",
                                    },
                                ])
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
                    and focus the <select> element once done`, () => {
                    const focus = jest.spyOn(host.artifact_link_select, "focus");

                    setNewLinks(host, NewLinkCollectionPresenter.fromLinks([]));

                    expect(host.allowed_link_types.is_parent_type_disabled).toBe(false);
                    expect(host.allowed_link_types.types).not.toHaveLength(0);
                    expect(focus).toHaveBeenCalledTimes(1);
                });
            });
        });
    });
});
