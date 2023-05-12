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

import type { GroupCollection } from "@tuleap/lazybox";
import { selectOrThrow } from "@tuleap/dom";
import { Option } from "@tuleap/option";
import { setCatalog } from "../../../../gettext-catalog";
import type { HostElement, LinkField } from "./LinkField";
import {
    current_link_type_descriptor,
    dropdown_section_descriptor,
    getEmptyStateIfNeeded,
    getLinkFieldCanOnlyHaveOneParentNote,
    getSkeletonIfNeeded,
    onLinkTypeChanged,
    setAllowedTypes,
    setLinkedArtifacts,
    setNewLinks,
} from "./LinkField";
import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import { ArtifactCrossReferenceStub } from "../../../../../tests/stubs/ArtifactCrossReferenceStub";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { LinkedArtifactStub } from "../../../../../tests/stubs/LinkedArtifactStub";
import type { NewLink } from "../../../../domain/fields/link-field/NewLink";
import { NewLinkStub } from "../../../../../tests/stubs/NewLinkStub";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { LazyboxStub } from "../../../../../tests/stubs/LazyboxStub";
import { LinkTypeStub } from "../../../../../tests/stubs/LinkTypeStub";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import { VerifyHasParentLinkStub } from "../../../../../tests/stubs/VerifyHasParentLinkStub";
import type { LinkFieldControllerType } from "./LinkFieldController";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import { MatchingArtifactsGroup } from "./dropdown/MatchingArtifactsGroup";
import { RecentlyViewedArtifactGroup } from "./dropdown/RecentlyViewedArtifactGroup";
import { PossibleParentsGroup } from "./dropdown/PossibleParentsGroup";
import { SearchResultsGroup } from "./dropdown/SearchResultsGroup";
import { LinkTypesCollectionStub } from "../../../../../tests/stubs/LinkTypesCollectionStub";
import type { ValueChangedEvent } from "./LinkTypeSelectorElement";
import type { ArtifactLinkSelectorAutoCompleterType } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import { ArtifactLinkFieldInfoStub } from "../../../../../tests/stubs/ArtifactLinkFieldInfoStub";

describe("LinkField", () => {
    beforeEach(() => {
        setCatalog({ getString: (msgid) => msgid });
    });

    describe("Display", () => {
        let target: ShadowRoot, current_artifact_reference: Option<ArtifactCrossReference>;

        function getHost(): HostElement {
            return {
                field_presenter: LinkFieldPresenter.fromField(
                    ArtifactLinkFieldInfoStub.withDefaults()
                ),
                current_artifact_reference,
            } as HostElement;
        }

        beforeEach(() => {
            current_artifact_reference = Option.nothing();
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
                const renderNote = getLinkFieldCanOnlyHaveOneParentNote(current_artifact_reference);
                const host = getHost();
                renderNote(host, target);

                expect(target.textContent?.trim()).toBe(
                    "Note: an artifact can only have one parent."
                );
            });

            it("When the modal is open in edition mode, Then the note displays the artifact reference in a badge", () => {
                current_artifact_reference = Option.fromValue(
                    ArtifactCrossReferenceStub.withRefAndColor("story #123", "red-wine")
                );

                const renderNote = getLinkFieldCanOnlyHaveOneParentNote(current_artifact_reference);
                const host = getHost();
                renderNote(host, target);

                const badge = selectOrThrow(target, "[data-test=artifact-cross-ref-badge]");
                expect(badge.textContent?.trim()).toBe("story #123");
                expect(badge.classList).toContain("tlp-swatch-red-wine");
            });
        });
    });

    describe(`setters`, () => {
        describe("dropdown_section_descriptor", () => {
            let link_selector: LazyboxStub;
            beforeEach(() => {
                link_selector = LazyboxStub.build();
            });

            const getHost = (): LinkField => {
                const initial_dropdown_content: GroupCollection = [];
                return {
                    controller: {} as LinkFieldControllerType,
                    link_selector: Option.fromValue(link_selector),
                    current_link_type: LinkTypeStub.buildUntyped(),
                    matching_artifact_section: initial_dropdown_content,
                    possible_parents_section: initial_dropdown_content,
                    recently_viewed_section: initial_dropdown_content,
                    search_results_section: initial_dropdown_content,
                } as LinkField;
            };

            it(`defaults property value to empty array`, () => {
                expect(dropdown_section_descriptor.set(getHost(), undefined)).toStrictEqual([]);
            });

            it(`replaces the link selector dropdown with the sections in the expected order when a section changes`, () => {
                const host = getHost();
                const setDropdown = jest.spyOn(link_selector, "replaceDropdownContent");
                host.matching_artifact_section = [MatchingArtifactsGroup.buildEmpty()];
                host.recently_viewed_section = [RecentlyViewedArtifactGroup.buildEmpty()];
                host.search_results_section = [SearchResultsGroup.buildEmpty()];
                host.possible_parents_section = [PossibleParentsGroup.buildEmpty()];

                dropdown_section_descriptor.observe(host);

                expect(setDropdown).toHaveBeenCalledWith([
                    ...host.matching_artifact_section,
                    ...host.recently_viewed_section,
                    ...host.search_results_section,
                    ...host.possible_parents_section,
                ]);
            });
        });

        describe(`current_link_type_descriptor`, () => {
            let host: LinkField;

            beforeEach(() => {
                host = {
                    autocompleter: {
                        autoComplete(): void {
                            //Do nothing
                        },
                    } as ArtifactLinkSelectorAutoCompleterType,
                    current_link_type: LinkTypeStub.buildUntyped(),
                } as LinkField;
            });

            const setType = (new_link_type: LinkType | undefined): LinkType => {
                return current_link_type_descriptor.set(host, new_link_type);
            };

            it(`defaults to Untyped link`, () => {
                const link_type = setType(undefined);
                expect(LinkType.isUntypedLink(link_type)).toBe(true);
            });

            it(`when the current type is changed,
                it will call the autocompleter with an empty string`, () => {
                const autoComplete = jest.spyOn(host.autocompleter, "autoComplete");

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

            it(`When the current link is a reverse child and the child link type is disabled,
                then it should default to Untyped link`, () => {
                host.current_link_type = LinkTypeStub.buildChildLinkType();
                setTypes(
                    CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                        VerifyHasParentLinkStub.withParentLink(),
                        LinkTypesCollectionStub.withParentPair()
                    )
                );

                expect(LinkType.isUntypedLink(host.current_link_type)).toBe(true);
            });
        });

        describe("link setters", () => {
            const getHost = (): LinkField =>
                ({
                    controller: {
                        displayAllowedTypes: () =>
                            CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                                VerifyHasParentLinkStub.withNoParentLink(),
                                LinkTypesCollectionStub.withParentPair()
                            ),
                    },
                } as LinkField);

            describe("setLinkedArtifacts", () => {
                it("should default to a loading state when there are no linked artifacts yet", () => {
                    const linked_artifacts = setLinkedArtifacts(getHost(), undefined);

                    expect(linked_artifacts.is_loading).toBe(true);
                    expect(linked_artifacts.has_loaded_content).toBe(false);
                    expect(linked_artifacts.linked_artifacts).toHaveLength(0);
                });

                it("should display allowed types when linked artifacts have been retrieved", () => {
                    const host = getHost();
                    setLinkedArtifacts(host, LinkedArtifactCollectionPresenter.fromArtifacts([]));

                    expect(host.allowed_link_types.is_parent_type_disabled).toBe(false);
                    expect(host.allowed_link_types.types).not.toHaveLength(0);
                });
            });

            describe("setNewLinks", () => {
                it("defaults to an empty NewLinkCollectionPresenter", () => {
                    const new_links = setNewLinks(getHost(), undefined);
                    expect(new_links).toHaveLength(0);
                });

                it(`should display allowed types when new links have been edited`, () => {
                    const host = getHost();
                    setNewLinks(host, NewLinkCollectionPresenter.fromLinks([]));

                    expect(host.allowed_link_types.is_parent_type_disabled).toBe(false);
                    expect(host.allowed_link_types.types).not.toHaveLength(0);
                });
            });
        });
    });

    describe(`events`, () => {
        it(`when it receives a value-changed event from the link type selector element,
            it will set the current link type`, () => {
            const host = {
                current_link_type: LinkTypeStub.buildUntyped(),
            } as LinkField;

            onLinkTypeChanged(
                host,
                new CustomEvent<ValueChangedEvent>("value-changed", {
                    detail: { new_link_type: LinkTypeStub.buildChildLinkType() },
                })
            );
            expect(LinkType.isReverseChild(host.current_link_type)).toBe(true);
        });
    });
});
