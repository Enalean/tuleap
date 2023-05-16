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
import { define, html } from "hybrids";
import { Option } from "@tuleap/option";
import type { GroupCollection, Lazybox, LazyboxOptions } from "@tuleap/lazybox";
import { createLazybox } from "@tuleap/lazybox";
import {
    getCreateNewArtifactButtonInLinkLabel,
    getLinkFieldCanHaveOnlyOneParent,
    getLinkFieldNoteStartText,
    getLinkFieldNoteText,
    getLinkFieldTableEmptyStateText,
    getLinkSelectorPlaceholderText,
    getLinkSelectorSearchPlaceholderText,
} from "../../../../gettext-catalog";
import type { LinkFieldControllerType } from "./LinkFieldController";
import { LinkedArtifactCollectionPresenter } from "./LinkedArtifactCollectionPresenter";
import { getLinkedArtifactTemplate } from "./LinkedArtifactTemplate";
import type { LinkFieldPresenter } from "./LinkFieldPresenter";
import {
    getLinkableArtifact,
    getLinkableArtifactTemplate,
} from "./dropdown/LinkableArtifactTemplate";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { getNewLinkTemplate } from "./NewLinkTemplate";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";
import type { TypeChangedEvent } from "./LinkTypeSelectorElement";
import "./LinkTypeSelectorElement";
import type { ArtifactLinkSelectorAutoCompleterType } from "./dropdown/ArtifactLinkSelectorAutoCompleter";
import type { ArtifactCrossReference } from "../../../../domain/ArtifactCrossReference";
import "./creation/ArtifactCreatorElement";
import type { ArtifactCreatorController } from "../../../../domain/fields/link-field/creation/ArtifactCreatorController";

export interface LinkField {
    readonly controller: LinkFieldControllerType;
    readonly autocompleter: ArtifactLinkSelectorAutoCompleterType;
    readonly creatorController: ArtifactCreatorController;
    current_artifact_reference: Option<ArtifactCrossReference>;
    field_presenter: LinkFieldPresenter;
    linked_artifacts_presenter: LinkedArtifactCollectionPresenter;
    allowed_link_types: CollectionOfAllowedLinksTypesPresenters;
    new_links_presenter: NewLinkCollectionPresenter;
    current_link_type: LinkType;
    matching_artifact_section: GroupCollection;
    recently_viewed_section: GroupCollection;
    possible_parents_section: GroupCollection;
    search_results_section: GroupCollection;
}
type InternalLinkField = LinkField & {
    content(): HTMLElement;
    link_selector: Option<Lazybox>;
    is_artifact_creator_shown: boolean;
};
export type HostElement = InternalLinkField & HTMLElement;

export const getEmptyStateIfNeeded = (host: LinkField): UpdateFunction<LinkField> => {
    if (
        host.linked_artifacts_presenter.linked_artifacts.length > 0 ||
        host.new_links_presenter.length > 0 ||
        !host.linked_artifacts_presenter.has_loaded_content
    ) {
        return html``;
    }

    return html`<div class="link-field-no-links-row" data-test="link-table-empty-state">
        ${getLinkFieldTableEmptyStateText()}
    </div>`;
};

export const getSkeletonIfNeeded = (
    presenter: LinkedArtifactCollectionPresenter
): UpdateFunction<LinkField> => {
    if (!presenter.is_loading) {
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

export const setNewLinks = (
    host: LinkField,
    presenter: NewLinkCollectionPresenter | undefined
): NewLinkCollectionPresenter => {
    if (!presenter) {
        return NewLinkCollectionPresenter.buildEmpty();
    }
    host.allowed_link_types = host.controller.displayAllowedTypes();
    return presenter;
};

export const setLinkedArtifacts = (
    host: LinkField,
    presenter: LinkedArtifactCollectionPresenter | undefined
): LinkedArtifactCollectionPresenter => {
    if (!presenter) {
        return LinkedArtifactCollectionPresenter.buildLoadingState();
    }

    host.allowed_link_types = host.controller.displayAllowedTypes();
    return presenter;
};

export const setAllowedTypes = (
    host: LinkField,
    presenter: CollectionOfAllowedLinksTypesPresenters | undefined
): CollectionOfAllowedLinksTypesPresenters => {
    if (!presenter) {
        return CollectionOfAllowedLinksTypesPresenters.buildEmpty();
    }
    if (LinkType.isReverseChild(host.current_link_type) && presenter.is_parent_type_disabled) {
        host.current_link_type = LinkType.buildUntyped();
    }
    return presenter;
};

export const dropdown_section_descriptor = {
    set: (host: InternalLinkField, collection: GroupCollection | undefined): GroupCollection =>
        collection ?? [],
    observe: (host: InternalLinkField): void => {
        host.link_selector.apply((lazybox) => {
            lazybox.replaceDropdownContent([
                ...host.matching_artifact_section,
                ...host.recently_viewed_section,
                ...host.search_results_section,
                ...host.possible_parents_section,
            ]);
        });
    },
};

export const current_link_type_descriptor = {
    set: (host: LinkField, link_type: LinkType | undefined): LinkType => {
        if (!link_type) {
            return LinkType.buildUntyped();
        }
        return link_type;
    },
    observe: (host: LinkField): void => {
        host.autocompleter.autoComplete(host, "");
    },
};

export const getLinkFieldCanOnlyHaveOneParentNote = (
    current_artifact_option: Option<ArtifactCrossReference>
): UpdateFunction<LinkField> => {
    const default_html = html`<p class="link-field-artifact-can-have-only-one-parent-note">
        ${getLinkFieldNoteText()}
    </p>`;
    return current_artifact_option.mapOr((current_artifact_reference) => {
        const { ref: artifact_reference, color } = current_artifact_reference;
        const badge_classes = [`tlp-swatch-${color}`, "cross-ref-badge"];
        return html`<p class="link-field-artifact-can-have-only-one-parent-note">
            ${getLinkFieldNoteStartText()}<span
                data-test="artifact-cross-ref-badge"
                class="${badge_classes}"
                >${artifact_reference}</span
            >${getLinkFieldCanHaveOnlyOneParent()}
        </p>`;
    }, default_html);
};

const onCancel = (host: InternalLinkField): void => {
    host.is_artifact_creator_shown = false;
    // Re-assign to call "replaceDropdownContent", otherwise the dropdown becomes empty
    host.matching_artifact_section = [...host.matching_artifact_section];
};

export const onLinkTypeChanged = (host: LinkField, event: CustomEvent<TypeChangedEvent>): void => {
    host.current_link_type = event.detail.new_link_type;
};

const getFooterTemplate = (host: InternalLinkField): UpdateFunction<LinkField> => {
    if (host.is_artifact_creator_shown) {
        return html`<tuleap-artifact-modal-link-artifact-creator
            controller="${host.creatorController}"
            current_link_type="${host.current_link_type}"
            current_artifact_reference="${host.current_artifact_reference}"
            available_types="${host.allowed_link_types}"
            oncancel="${onCancel}"
            ontype-changed="${onLinkTypeChanged}"
        ></tuleap-artifact-modal-link-artifact-creator>`;
    }
    const link_selector = host.link_selector.mapOr((element) => html`${element}`, html``);
    return html`<div class="link-field-add-link-row">
        <span class="link-field-row-type">
            <tuleap-artifact-modal-link-type-selector
                value="${host.current_link_type}"
                current_artifact_reference="${host.current_artifact_reference}"
                available_types="${host.allowed_link_types}"
                ontype-changed="${onLinkTypeChanged}"
            ></tuleap-artifact-modal-link-type-selector>
        </span>
        <div class="link-field-add-link-input">${link_selector}</div>
    </div>`;
};

const createLazyBox = (host: HostElement, is_feature_flag_enabled: boolean): void => {
    const options_with_feature_flag = is_feature_flag_enabled
        ? {
              new_item_callback: (): void => {
                  host.is_artifact_creator_shown = true;
              },
              new_item_button_label: getCreateNewArtifactButtonInLinkLabel(),
          }
        : {};

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
        selection_callback: (value) => {
            const artifact = getLinkableArtifact(value);
            if (artifact) {
                link_selector.clearSelection();
                host.new_links_presenter = host.controller.addNewLink(
                    artifact,
                    host.current_link_type
                );
            }
        },
        ...options_with_feature_flag,
    };
    link_selector.options = options;
    host.link_selector = Option.fromValue(link_selector);
};

export const LinkField = define<InternalLinkField>({
    tag: "tuleap-artifact-modal-link-field",
    link_selector: {
        set: (host, new_value) => new_value ?? Option.nothing(),
    },
    controller: {
        set(host, controller: LinkFieldControllerType) {
            host.current_artifact_reference = controller.getCurrentArtifactReference();
            host.field_presenter = controller.displayField();
            host.allowed_link_types = controller.displayAllowedTypes();
            controller.displayLinkedArtifacts().then((artifacts) => {
                host.linked_artifacts_presenter = artifacts;
            });
            controller.getFeatureFlag().then((is_feature_flag_enabled) => {
                createLazyBox(host, is_feature_flag_enabled);
            });
            controller.getPossibleParents().then((parents) => {
                host.current_link_type = controller.getCurrentLinkType(parents.length > 0);
                host.allowed_link_types = controller.displayAllowedTypes();
            });
            return controller;
        },
    },
    autocompleter: undefined,
    creatorController: undefined,
    current_artifact_reference: { set: (host, new_value) => new_value ?? Option.nothing() },
    field_presenter: undefined,
    allowed_link_types: { set: setAllowedTypes },
    linked_artifacts_presenter: { set: setLinkedArtifacts },
    new_links_presenter: { set: setNewLinks },
    current_link_type: current_link_type_descriptor,
    matching_artifact_section: dropdown_section_descriptor,
    recently_viewed_section: dropdown_section_descriptor,
    possible_parents_section: dropdown_section_descriptor,
    search_results_section: dropdown_section_descriptor,
    is_artifact_creator_shown: false,
    content: (host) => html`<div class="tracker-form-element" data-test="artifact-link-field">
        <label class="tlp-label">${host.field_presenter.label}</label>
        ${getLinkFieldCanOnlyHaveOneParentNote(host.current_artifact_reference)}
        <div class="link-field-rows-wrapper">
            ${host.linked_artifacts_presenter.linked_artifacts.map((link) =>
                getLinkedArtifactTemplate(host, link)
            )}
            ${host.new_links_presenter.map((link) => getNewLinkTemplate(host, link))}
            ${getSkeletonIfNeeded(host.linked_artifacts_presenter)}${getEmptyStateIfNeeded(host)}
        </div>
        <div class="link-field-add-link-section">${getFooterTemplate(host)}</div>
    </div>`,
});
