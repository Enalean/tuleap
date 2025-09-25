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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { GroupCollection } from "@tuleap/lazybox";
import { Option } from "@tuleap/option";
import { setTranslator } from "../../gettext-catalog";
import type { HostElement, LinkField } from "./LinkField";
import {
    current_link_type_descriptor,
    dropdown_section_descriptor,
    getAllowedLinkTypes,
    getEmptyStateIfNeeded,
    getLinkedArtifactPresenters,
    getSkeletonIfNeeded,
    observeArtifactCreator,
    observeNewLinks,
    onArtifactCreated,
    onCancel,
    onLinkTypeChanged,
} from "./LinkField";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import { LinkedArtifactStub } from "../../../tests/stubs/links/LinkedArtifactStub";
import { NewLink } from "../../domain/links/NewLink";
import { NewLinkStub } from "../../../tests/stubs/links/NewLinkStub";
import { LinkType } from "../../domain/links/LinkType";
import { LazyboxStub } from "../../../tests/stubs/LazyboxStub";
import { LinkTypeStub } from "../../../tests/stubs/links/LinkTypeStub";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import type { LinkFieldController } from "../../domain/LinkFieldController";
import type { ArtifactCrossReference } from "../../domain/ArtifactCrossReference";
import { MatchingArtifactsGroup } from "./dropdown/MatchingArtifactsGroup";
import { RecentlyViewedArtifactGroup } from "./dropdown/RecentlyViewedArtifactGroup";
import { PossibleParentsGroup } from "./dropdown/PossibleParentsGroup";
import { SearchResultsGroup } from "./dropdown/SearchResultsGroup";
import { LinkTypesCollectionStub } from "../../../tests/stubs/links/LinkTypesCollectionStub";
import type { TypeChangedEvent } from "./LinkTypeSelectorElement";
import type { ArtifactLinkSelectorAutoCompleterType } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import { LabeledFieldStub } from "../../../tests/stubs/LabeledFieldStub";
import type { ArtifactCreatedEvent } from "./creation/ArtifactCreatorElement";
import { LinkableArtifactStub } from "../../../tests/stubs/links/LinkableArtifactStub";
import type { LinkedArtifact, LinkedArtifactIdentifier } from "../../domain/links/LinkedArtifact";
import { LinkedArtifactIdentifierStub } from "../../../tests/stubs/links/LinkedArtifactIdentifierStub";

describe("LinkField", () => {
    let doc: Document;
    beforeEach(() => {
        setTranslator({ gettext: (msgid) => msgid });
        doc = document.implementation.createHTMLDocument();
    });

    describe("Display", () => {
        let target: ShadowRoot,
            current_artifact_reference: Option<ArtifactCrossReference>,
            is_loading_links: boolean;

        beforeEach(() => {
            current_artifact_reference = Option.nothing();
            is_loading_links = false;
            target = doc.createElement("div") as unknown as ShadowRoot;
        });

        const getHost = (): HostElement =>
            ({
                field_presenter: LabeledFieldStub.withDefaults(),
                current_artifact_reference,
                is_loading_links,
            }) as HostElement;

        it("should render a skeleton row when the links are being loaded", () => {
            is_loading_links = true;
            const host = getHost();
            const render = getSkeletonIfNeeded(host);
            render(host, target);
            expect(target.querySelector("[data-test=link-field-table-skeleton]")).not.toBeNull();
        });

        describe(`emptyState`, () => {
            let linked_artifacts: readonly LinkedArtifactPresenter[], new_links: readonly NewLink[];

            beforeEach(() => {
                linked_artifacts = [];
                new_links = [];
            });

            const renderField = (): void => {
                const presenters: ReadonlyArray<LinkedArtifactPresenter> = linked_artifacts.map(
                    (artifact) =>
                        LinkedArtifactPresenter.fromLinkedArtifact(artifact, false, false),
                );
                const host = {
                    linked_artifact_presenters: presenters,
                    new_links,
                } as HostElement;
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
                        false,
                        false,
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
    });

    describe(`property descriptors`, () => {
        describe("dropdown_section_descriptor", () => {
            let link_selector: LazyboxStub;
            beforeEach(() => {
                link_selector = LazyboxStub.build();
            });

            const getHost = (): HostElement => {
                const initial_dropdown_content: GroupCollection = [];
                return {
                    controller: {} as LinkFieldController,
                    link_selector,
                    current_link_type: LinkTypeStub.buildDefaultLinkType(),
                    matching_artifact_section: initial_dropdown_content,
                    possible_parents_section: initial_dropdown_content,
                    recently_viewed_section: initial_dropdown_content,
                    search_results_section: initial_dropdown_content,
                } as HostElement;
            };

            it(`replaces the link selector dropdown with the sections in the expected order when a section changes`, () => {
                const host = getHost();
                const setDropdown = vi.spyOn(link_selector, "replaceDropdownContent");
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
            let is_parent_type_disabled: boolean;

            beforeEach(() => {
                is_parent_type_disabled = false;
            });

            const getHost = (): HostElement => {
                return {
                    autocompleter: {
                        autoComplete(): void {
                            //Do nothing
                        },
                    } as ArtifactLinkSelectorAutoCompleterType,
                    allowed_link_types:
                        CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                            is_parent_type_disabled,
                            LinkTypesCollectionStub.withParentPair(),
                        ),
                    current_link_type: LinkTypeStub.buildDefaultLinkType(),
                } as HostElement;
            };

            const getType = (new_link_type: LinkType | undefined): LinkType => {
                return current_link_type_descriptor.value(getHost(), new_link_type);
            };

            it(`defaults to Untyped link`, () => {
                const link_type = getType(undefined);
                expect(LinkType.isDefaultTypeLabel(link_type)).toBe(true);
            });

            it(`When the current link is a reverse child and the child link type is disabled,
                then it should default to Untyped link`, () => {
                is_parent_type_disabled = true;
                const current_link_type = getType(LinkTypeStub.buildChildLinkType());

                expect(LinkType.isDefaultTypeLabel(current_link_type)).toBe(true);
            });

            it(`when the current link is a reverse child and the child link type is allowed,
                then it will return reverse child type`, () => {
                is_parent_type_disabled = false;
                const new_link_type = LinkTypeStub.buildChildLinkType();

                expect(getType(new_link_type)).toBe(new_link_type);
            });

            it(`when the current type is changed,
                it will call the autocompleter with an empty string`, () => {
                const host = getHost();
                const autoComplete = vi.spyOn(host.autocompleter, "autoComplete");

                current_link_type_descriptor.observe(host);

                expect(autoComplete).toHaveBeenCalledWith(host, "");
            });
        });

        describe(`getAllowedLinkTypes()`, () => {
            const getHost = (): LinkField => {
                const linked_artifacts: ReadonlyArray<LinkedArtifact> = [];
                const new_links: ReadonlyArray<NewLink> = [];
                return {
                    current_link_type: LinkTypeStub.buildDefaultLinkType(),
                    linked_artifacts,
                    new_links,
                } as LinkField;
            };

            it("defaults to a en empty CollectionOfAllowedLinksTypesPresenters", () => {
                const allowed_types = getAllowedLinkTypes(getHost(), undefined);
                expect(allowed_types.types).toHaveLength(0);
                expect(allowed_types.is_parent_type_disabled).toBe(false);
            });
        });

        describe("link descriptors", () => {
            const getHost = (): HostElement => {
                const element = doc.createElement("div");
                const parent_artifact_ids: ReadonlyArray<LinkedArtifactIdentifier> = [];
                const linked_artifacts: ReadonlyArray<LinkedArtifact> = [];
                return Object.assign(element, {
                    controller: {
                        isMarkedForRemoval: (link) => (link ? false : false),
                    },
                    linked_artifacts,
                    parent_artifact_ids,
                } as HostElement);
            };

            describe("getLinkedArtifactPresenters()", () => {
                it("should default to an empty array", () => {
                    const host = getHost();
                    expect(getLinkedArtifactPresenters(host)).toHaveLength(0);
                });

                it(`should set the presenters property when linked artifacts have been retrieved`, () => {
                    const host = getHost();
                    host.linked_artifacts = [LinkedArtifactStub.withDefaults()];
                    expect(getLinkedArtifactPresenters(host)).toHaveLength(1);
                });

                it(`should sort the parent links at the beginning of the list of linked artifacts
                    and mark the presenters with "is_parent"`, () => {
                    const host = getHost();
                    const first_parent_id = LinkedArtifactIdentifierStub.withId(89),
                        second_parent_id = LinkedArtifactIdentifierStub.withId(27),
                        first_other_id = LinkedArtifactIdentifierStub.withId(73),
                        second_other_id = LinkedArtifactIdentifierStub.withId(46);
                    host.parent_artifact_ids = [first_parent_id, second_parent_id];
                    host.linked_artifacts = [
                        LinkedArtifactStub.withDefaults({ identifier: first_other_id }),
                        LinkedArtifactStub.withDefaults({ identifier: first_parent_id }),
                        LinkedArtifactStub.withDefaults({ identifier: second_other_id }),
                        LinkedArtifactStub.withDefaults({ identifier: second_parent_id }),
                    ];
                    const presenters = getLinkedArtifactPresenters(host);

                    expect(presenters).toHaveLength(4);
                    const [first_link, second_link, third_link, fourth_link] = presenters;
                    expect(first_link.identifier).toBe(first_parent_id);
                    expect(first_link.is_parent).toBe(true);
                    expect(second_link.identifier).toBe(second_parent_id);
                    expect(second_link.is_parent).toBe(true);
                    expect(third_link.identifier).toBe(first_other_id);
                    expect(third_link.is_parent).toBe(false);
                    expect(fourth_link.identifier).toBe(second_other_id);
                    expect(fourth_link.is_parent).toBe(false);
                });
            });

            describe("observeNewLinks()", () => {
                it(`dispatches a bubbling "change" event`, () => {
                    const host = getHost();
                    const dispatchEvent = vi.spyOn(host, "dispatchEvent");

                    observeNewLinks(host, [NewLinkStub.withDefaults()], []);

                    const event = dispatchEvent.mock.calls[0][0];
                    expect(event.type).toBe("change");
                    expect(event.bubbles).toBe(true);
                });

                it(`does not trigger when the property is defined for the first time`, () => {
                    const host = getHost();
                    const dispatchEvent = vi.spyOn(host, "dispatchEvent");

                    observeNewLinks(host, [], undefined);

                    expect(dispatchEvent).not.toHaveBeenCalled();
                });
            });
        });
    });

    describe(`observeArtifactCreator()`, () => {
        const getLazybox = (): HTMLElement & LazyboxStub =>
            Object.assign(doc.createElement("div"), LazyboxStub.build());

        it(`when is_artifact_creator_shown becomes false, it focuses the link selector`, () => {
            const lazybox = getLazybox();
            const focus = vi.spyOn(lazybox, "focus");
            const host = {
                is_artifact_creator_shown: true,
                link_selector: lazybox,
                render: () => doc.createElement("div") as HTMLElement,
            } as HostElement;
            observeArtifactCreator(host, false);

            expect(focus).toHaveBeenCalled();
        });
    });

    describe(`events`, () => {
        it(`when it receives a "type-changed" event from the link type selector element,
            it will set the current link type`, () => {
            const host = {
                current_link_type: LinkTypeStub.buildDefaultLinkType(),
            } as LinkField;

            onLinkTypeChanged(
                host,
                new CustomEvent<TypeChangedEvent>("type-changed", {
                    detail: { new_link_type: LinkTypeStub.buildChildLinkType() },
                }),
            );
            expect(LinkType.isReverseChild(host.current_link_type)).toBe(true);
        });

        it(`when it receives a "cancel" event from the artifact creator element,
            it will hide the artifact creator element`, () => {
            const host = {
                is_artifact_creator_shown: true,
            } as HostElement;

            onCancel(host);

            expect(host.is_artifact_creator_shown).toBe(false);
        });

        it(`when it receives an "artifact-created" event from the artifact creator element,
            it will hide the artifact creator element
            and tell the controller to add the NewLink from the event
            and assign the list of new links with the result`, () => {
            const ARTIFACT_ID = 278;
            const new_links: ReadonlyArray<NewLink> = [];

            const host = {
                controller: {
                    addNewLink(artifact, type): ReadonlyArray<NewLink> {
                        return [NewLink.fromLinkableArtifactAndType(artifact, type)];
                    },
                },
                current_link_type: LinkTypeStub.buildChildLinkType(),
                new_links,
                is_artifact_creator_shown: true,
            } as HostElement;

            const artifact = LinkableArtifactStub.withDefaults({ id: ARTIFACT_ID });
            onArtifactCreated(
                host,
                new CustomEvent<ArtifactCreatedEvent>("artifact-created", { detail: { artifact } }),
            );

            expect(host.is_artifact_creator_shown).toBe(false);
            expect(host.new_links).toHaveLength(1);
            const [new_link] = host.new_links;
            expect(new_link.identifier.id).toBe(ARTIFACT_ID);
            expect(LinkType.isReverseChild(new_link.link_type)).toBe(true);
        });
    });
});
