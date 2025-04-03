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

import type { UpdateFunction } from "hybrids";
import { define, dispatch, html } from "hybrids";
import type { Option } from "@tuleap/option";
import type { GroupCollection, Lazybox, LazyboxOptions } from "@tuleap/lazybox";
import { createLazybox } from "@tuleap/lazybox";
import { sprintf } from "sprintf-js";
import {
    getCreateNewArtifactButtonInLinkLabel,
    getCreateNewArtifactButtonInLinkWithNameLabel,
    getLinkFieldTableEmptyStateText,
    getLinkSelectorPlaceholderText,
    getLinkSelectorSearchPlaceholderText,
    getSubmitDisabledForLinksReason,
} from "../../gettext-catalog";
import type { LinkFieldController } from "../../domain/LinkFieldController";
import { getLinkedArtifactTemplate } from "./LinkedArtifactTemplate";
import type { LabeledField } from "../../domain/LabeledField";
import {
    getLinkableArtifact,
    getLinkableArtifactTemplate,
} from "./dropdown/LinkableArtifactTemplate";
import { LinkType } from "../../domain/links/LinkType";
import { getNewLinkTemplate } from "./NewLinkTemplate";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import type { TypeChangedEvent } from "./LinkTypeSelectorElement";
import "./LinkTypeSelectorElement";
import type { ArtifactLinkSelectorAutoCompleterType } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import type { ArtifactCrossReference } from "../../domain/ArtifactCrossReference";
import type { ArtifactCreatedEvent } from "./creation/ArtifactCreatorElement";
import "./creation/ArtifactCreatorElement";
import type { ArtifactCreatorController } from "../../domain/creation/ArtifactCreatorController";
import { LinkedArtifactPresenter } from "./LinkedArtifactPresenter";
import type { LinkedArtifact, LinkedArtifactIdentifier } from "../../domain/links/LinkedArtifact";
import type { NewLink } from "../../domain/links/NewLink";

export interface ExternalLinkField {
    controller: LinkFieldController;
    autocompleter: ArtifactLinkSelectorAutoCompleterType;
    creatorController: ArtifactCreatorController;
}

export interface LinkField extends Readonly<ExternalLinkField> {
    current_artifact_reference: Option<ArtifactCrossReference>;
    field_presenter: LabeledField;
    allowed_link_types: CollectionOfAllowedLinksTypesPresenters;
    linked_artifacts: ReadonlyArray<LinkedArtifact>;
    new_links: ReadonlyArray<NewLink>;
    current_link_type: LinkType;
    matching_artifact_section: GroupCollection;
    recently_viewed_section: GroupCollection;
    possible_parents_section: GroupCollection;
    search_results_section: GroupCollection;
}
type InternalLinkField = LinkField & {
    render(): HTMLElement;
    link_selector: Lazybox & HTMLElement;
    is_artifact_creator_shown: boolean;
    is_loading_links: boolean;
    linked_artifact_presenters: ReadonlyArray<LinkedArtifactPresenter>;
    parent_artifact_ids: ReadonlyArray<LinkedArtifactIdentifier>;
    new_artifact_title: string;
};
export type HostElement = InternalLinkField & HTMLElement;

export const TAG = "tuleap-tracker-link-field";

export const getEmptyStateIfNeeded = (host: InternalLinkField): UpdateFunction<LinkField> => {
    if (
        host.linked_artifact_presenters.length > 0 ||
        host.new_links.length > 0 ||
        host.is_loading_links
    ) {
        return html``;
    }

    return html`<div class="link-field-no-links-row" data-test="link-table-empty-state">
        ${getLinkFieldTableEmptyStateText()}
    </div>`;
};

export const getSkeletonIfNeeded = (host: InternalLinkField): UpdateFunction<LinkField> => {
    if (!host.is_loading_links) {
        return html``;
    }
    return html`<div
        class="link-field-row link-field-skeleton-row"
        data-test="link-field-table-skeleton"
    >
        <span class="link-field-row-type"><span class="tlp-skeleton-text"></span></span
        ><span class="link-field-row-xref"
            ><span class="link-field-artifact-link"
                ><i
                    class="fa-solid fa-hashtag tlp-skeleton-text-icon tlp-skeleton-icon"
                    aria-hidden="true"
                ></i
                ><span class="link-field-artifact-title tlp-skeleton-text"></span></span></span
        ><span class="tlp-skeleton-text"></span><span class="tlp-skeleton-text"></span>
    </div>`;
};

export const getLinkedArtifactPresenters = (
    host: InternalLinkField,
): ReadonlyArray<LinkedArtifactPresenter> => {
    const parents = host.linked_artifacts.filter((link) =>
        host.parent_artifact_ids.includes(link.identifier),
    );
    const not_parents = host.linked_artifacts.filter(
        (link) => !host.parent_artifact_ids.includes(link.identifier),
    );
    const sorted_links = [...parents, ...not_parents];

    return sorted_links.map((artifact) =>
        LinkedArtifactPresenter.fromLinkedArtifact(
            artifact,
            parents.includes(artifact),
            host.controller.isMarkedForRemoval(artifact),
        ),
    );
};

export const observeNewLinks = (
    host: HostElement,
    new_links: unknown,
    old_links: unknown | undefined,
): void => {
    if (old_links === undefined) {
        return;
    }
    dispatch(host, "change", { bubbles: true });
};

export const dropdown_section_descriptor = {
    value: (host: InternalLinkField, value: GroupCollection): GroupCollection => value ?? [],
    observe: (host: InternalLinkField): void => {
        host.link_selector.replaceDropdownContent([
            ...host.matching_artifact_section,
            ...host.recently_viewed_section,
            ...host.search_results_section,
            ...host.possible_parents_section,
        ]);
    },
};

export const getAllowedLinkTypes = (
    host: LinkField,
    value: unknown | undefined,
): CollectionOfAllowedLinksTypesPresenters => {
    if (!value) {
        return CollectionOfAllowedLinksTypesPresenters.buildEmpty();
    }

    return CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
        // We must pass properties from the host, so that hybrids can recompute
        // the allowed link types when either the linked_artifacts or the new_links
        // change.
        host.controller.hasParentLink(host.linked_artifacts, host.new_links),
        host.controller.getAllowedLinkTypes(),
    );
};

export const current_link_type_descriptor = {
    value: (host: LinkField, link_type: LinkType | undefined): LinkType => {
        if (!link_type) {
            return LinkType.buildUntyped();
        }
        if (LinkType.isReverseChild(link_type) && host.allowed_link_types.is_parent_type_disabled) {
            return LinkType.buildUntyped();
        }
        return link_type;
    },
    observe: (host: LinkField): void => {
        host.autocompleter.autoComplete(host, "");
    },
};

export const onCancel = (host: InternalLinkField): void => {
    host.is_artifact_creator_shown = false;
};

export const onArtifactCreated = (
    host: InternalLinkField,
    event: CustomEvent<ArtifactCreatedEvent>,
): void => {
    host.new_links = host.controller.addNewLink(event.detail.artifact, host.current_link_type);
    host.is_artifact_creator_shown = false;
};

export const observeArtifactCreator = (host: InternalLinkField, new_value: boolean): void => {
    if (!new_value) {
        host.render();
        host.link_selector.focus();
    }
};

export const onLinkTypeChanged = (host: LinkField, event: CustomEvent<TypeChangedEvent>): void => {
    host.current_link_type = event.detail.new_link_type;
};

const getFooterTemplate = (host: InternalLinkField): UpdateFunction<LinkField> => {
    if (host.is_artifact_creator_shown) {
        return html`<tuleap-tracker-link-artifact-creator
            controller="${host.creatorController}"
            current_link_type="${host.current_link_type}"
            current_artifact_reference="${host.current_artifact_reference}"
            available_types="${host.allowed_link_types}"
            artifact_title="${host.new_artifact_title}"
            oncancel="${onCancel}"
            ontype-changed="${onLinkTypeChanged}"
            onartifact-created="${onArtifactCreated}"
        ></tuleap-tracker-link-artifact-creator>`;
    }
    return html`<div class="link-field-add-link-row">
        <span class="link-field-row-type">
            <tuleap-tracker-link-type-selector
                value="${host.current_link_type}"
                current_artifact_reference="${host.current_artifact_reference}"
                available_types="${host.allowed_link_types}"
                ontype-changed="${onLinkTypeChanged}"
            ></tuleap-tracker-link-type-selector>
        </span>
        <div class="link-field-add-link-input">${host.link_selector}</div>
    </div>`;
};

const createLazyBox = (host: HostElement): Lazybox & HTMLElement => {
    const link_selector = createLazybox(host.ownerDocument);
    const options: LazyboxOptions = {
        is_multiple: false,
        placeholder: getLinkSelectorPlaceholderText(),
        search_input_placeholder: getLinkSelectorSearchPlaceholderText(),
        search_input_callback: (query) => {
            host.controller.clearFaultNotification();
            host.autocompleter.autoComplete(host, query);
        },
        templating_callback: getLinkableArtifactTemplate,
        selection_callback: (value) =>
            getLinkableArtifact(value).apply((artifact) => {
                link_selector.clearSelection();
                host.new_links = host.controller.addNewLink(artifact, host.current_link_type);
            }),
        new_item_clicked_callback: (title: string): void => {
            host.is_artifact_creator_shown = true;
            host.new_artifact_title = title;
        },
        new_item_label_callback: (title: string) =>
            title !== ""
                ? sprintf(getCreateNewArtifactButtonInLinkWithNameLabel(), { title })
                : getCreateNewArtifactButtonInLinkLabel(),
    };
    link_selector.options = options;
    return link_selector;
};

export const LinkField = define.compile<InternalLinkField>({
    tag: TAG,
    link_selector: (host: HostElement) => createLazyBox(host),
    controller: {
        value: (host, controller) => controller,
        observe(host, controller) {
            Promise.all([
                controller.getLinkedArtifacts(getSubmitDisabledForLinksReason()),
                controller.getPossibleParents(),
            ]).then(([artifacts, parents]) => {
                host.parent_artifact_ids = artifacts
                    .filter((link) => LinkType.isReverseChild(link.link_type))
                    .map((link) => link.identifier);
                host.linked_artifacts = artifacts;
                host.is_loading_links = false;

                host.current_link_type = host.controller.getCurrentLinkType(
                    parents.length > 0,
                    host.linked_artifacts,
                    host.new_links,
                );
                host.allowed_link_types =
                    CollectionOfAllowedLinksTypesPresenters.fromCollectionOfAllowedLinkType(
                        host.controller.hasParentLink(host.linked_artifacts, host.new_links),
                        host.controller.getAllowedLinkTypes(),
                    );
            });
        },
    },
    autocompleter: (host, autocompleter) => autocompleter,
    creatorController: (host, creator_controller) => creator_controller,
    current_artifact_reference: (host: InternalLinkField) =>
        host.controller.getCurrentArtifactReference(),
    field_presenter: (host: InternalLinkField) => host.controller.getLabeledField(),
    allowed_link_types: getAllowedLinkTypes,
    linked_artifacts: { value: [] },
    linked_artifact_presenters: getLinkedArtifactPresenters,
    parent_artifact_ids: { value: [] },
    is_loading_links: true,
    new_links: {
        value: [],
        observe: observeNewLinks,
    },
    current_link_type: current_link_type_descriptor,
    matching_artifact_section: dropdown_section_descriptor,
    recently_viewed_section: dropdown_section_descriptor,
    possible_parents_section: dropdown_section_descriptor,
    search_results_section: dropdown_section_descriptor,
    is_artifact_creator_shown: false,
    new_artifact_title: "",
    render: (host) =>
        html`<div class="tracker-form-element" data-test="artifact-link-field">
            <label class="tlp-label tracker_formelement_label">${host.field_presenter.label}</label>
            <div class="link-field-rows-wrapper">
                ${host.linked_artifact_presenters.map((link) =>
                    getLinkedArtifactTemplate(host, link),
                )}
                ${host.new_links.map((link) => getNewLinkTemplate(host, link))}
                ${getSkeletonIfNeeded(host)}${getEmptyStateIfNeeded(host)}
            </div>
            <div class="link-field-add-link-section" data-test="link-field-add-link-section">
                ${getFooterTemplate(host)}
            </div>
        </div>`,
});

if (!window.customElements.get(TAG)) {
    window.customElements.define(TAG, LinkField);
}
