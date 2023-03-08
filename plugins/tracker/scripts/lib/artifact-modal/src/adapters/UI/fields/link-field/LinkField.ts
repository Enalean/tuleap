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
import {
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
import { getTypeSelectorTemplate } from "./TypeSelectorTemplate";
import type { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { GroupCollection, LinkSelector } from "@tuleap/link-selector";
import { createLinkSelector } from "@tuleap/link-selector";
import {
    getLinkableArtifact,
    getLinkableArtifactTemplate,
} from "./dropdown/LinkableArtifactTemplate";
import { LinkType } from "../../../../domain/fields/link-field/LinkType";
import { NewLinkCollectionPresenter } from "./NewLinkCollectionPresenter";
import { getNewLinkTemplate } from "./NewLinkTemplate";
import { CollectionOfAllowedLinksTypesPresenters } from "./CollectionOfAllowedLinksTypesPresenters";

export interface LinkField {
    readonly content: () => HTMLElement;
    readonly controller: LinkFieldControllerType;
    readonly artifact_link_select: HTMLSelectElement;
    link_selector: LinkSelector;
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
export type HostElement = LinkField & HTMLElement;

export const getEmptyStateIfNeeded = (host: LinkField): UpdateFunction<LinkField> => {
    if (
        host.linked_artifacts_presenter.linked_artifacts.length > 0 ||
        host.new_links_presenter.length > 0 ||
        !host.linked_artifacts_presenter.has_loaded_content
    ) {
        return html``;
    }

    return html`
        <tr class="link-field-table-row link-field-no-links-row" data-test="link-table-empty-state">
            <td class="link-field-table-cell-no-links tlp-table-cell-empty" colspan="4">
                ${getLinkFieldTableEmptyStateText()}
            </td>
        </tr>
    `;
};

export const getSkeletonIfNeeded = (
    presenter: LinkedArtifactCollectionPresenter
): UpdateFunction<LinkField> => {
    if (!presenter.is_loading) {
        return html``;
    }

    return html`
        <tr
            class="link-field-table-row link-field-skeleton-row"
            data-test="link-field-table-skeleton"
        >
            <td class="link-field-table-cell-type link-field-skeleton-cell">
                <span class="tlp-skeleton-text"></span>
            </td>
            <td class="link-field-table-cell-xref link-field-skeleton-cell">
                <i
                    class="fas fa-hashtag tlp-skeleton-text-icon tlp-skeleton-icon"
                    aria-hidden="true"
                ></i>
                <span class="tlp-skeleton-text"></span>
            </td>
            <td class="link-field-table-cell-status link-field-skeleton-cell">
                <span class="tlp-skeleton-text"></span>
            </td>
            <td class="link-field-table-cell-status link-field-table-cell-action">
                <span class="tlp-skeleton-text"></span>
            </td>
        </tr>
    `;
};

export const setNewLinks = (
    host: LinkField,
    presenter: NewLinkCollectionPresenter | undefined
): NewLinkCollectionPresenter => {
    if (!presenter) {
        return NewLinkCollectionPresenter.buildEmpty();
    }
    host.allowed_link_types = host.controller.displayAllowedTypes();
    host.artifact_link_select.focus();

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
    set: (host: LinkField, collection: GroupCollection | undefined): GroupCollection =>
        collection ?? [],
    observe: (host: LinkField): void => {
        host.link_selector.setDropdownContent([
            ...host.matching_artifact_section,
            ...host.recently_viewed_section,
            ...host.search_results_section,
            ...host.possible_parents_section,
        ]);
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
        host.controller.autoComplete(host, "");
    },
};

export const getLinkFieldCanOnlyHaveOneParentNote = (
    host: LinkField
): UpdateFunction<LinkField> => {
    if (!host.field_presenter.current_artifact_reference) {
        return html`
            <p class="link-field-artifact-can-have-only-one-parent-note">
                ${getLinkFieldNoteText()}
            </p>
        `;
    }

    const { ref: artifact_reference, color } = host.field_presenter.current_artifact_reference;
    const badge_classes = [
        `tlp-swatch-${color}`,
        "cross-ref-badge",
        "link-field-parent-note-xref-badge",
    ];
    return html`
        <p class="link-field-artifact-can-have-only-one-parent-note">
            ${getLinkFieldNoteStartText()}
            <span data-test="artifact-cross-ref-badge" class="${badge_classes}">
                ${artifact_reference}
            </span>
            ${getLinkFieldCanHaveOnlyOneParent()}
        </p>
    `;
};

export const LinkField = define<LinkField>({
    tag: "tuleap-artifact-modal-link-field",
    artifact_link_select: ({ content }) => {
        const select = content().querySelector(`[data-select=artifact-link-select]`);
        if (!(select instanceof HTMLSelectElement)) {
            throw new Error("Unable to find the artifact-link-select");
        }

        return select;
    },
    link_selector: undefined,
    controller: {
        set(host, controller: LinkFieldControllerType) {
            host.field_presenter = controller.displayField();
            host.allowed_link_types = controller.displayAllowedTypes();
            controller.displayLinkedArtifacts().then((artifacts) => {
                host.linked_artifacts_presenter = artifacts;
            });

            host.link_selector = createLinkSelector(host.artifact_link_select, {
                placeholder: getLinkSelectorPlaceholderText(),
                search_input_placeholder: getLinkSelectorSearchPlaceholderText(),
                search_field_callback: (query) => {
                    host.controller.clearFaultNotification();
                    return controller.autoComplete(host, query);
                },
                templating_callback: getLinkableArtifactTemplate,
                selection_callback: (value) => {
                    const artifact = getLinkableArtifact(value);
                    if (artifact) {
                        host.link_selector.resetSelection();
                        host.new_links_presenter = controller.addNewLink(
                            artifact,
                            host.current_link_type
                        );
                    }
                },
            });

            controller.retrievePossibleParentsGroups().then((group) => {
                host.current_link_type = controller.getCurrentLinkType(group.items.length > 0);
                host.allowed_link_types = controller.displayAllowedTypes();
            });
            return controller;
        },
    },
    field_presenter: undefined,
    allowed_link_types: {
        set: setAllowedTypes,
    },
    linked_artifacts_presenter: {
        set: setLinkedArtifacts,
    },
    new_links_presenter: {
        set: setNewLinks,
    },
    current_link_type: current_link_type_descriptor,
    matching_artifact_section: dropdown_section_descriptor,
    recently_viewed_section: dropdown_section_descriptor,
    possible_parents_section: dropdown_section_descriptor,
    search_results_section: dropdown_section_descriptor,
    content: (host) => html`
        <div class="tracker-form-element" data-test="artifact-link-field">
            <label for="${"tracker_field_" + host.field_presenter.field_id}" class="tlp-label">
                ${host.field_presenter.label}
            </label>
            ${getLinkFieldCanOnlyHaveOneParentNote(host)}
            <table id="tuleap-artifact-modal-link-table" class="tlp-table">
                <tbody class="link-field-table-body">
                    ${host.linked_artifacts_presenter.linked_artifacts.map(
                        getLinkedArtifactTemplate
                    )}
                    ${host.new_links_presenter.map(getNewLinkTemplate)}
                    ${getSkeletonIfNeeded(host.linked_artifacts_presenter)}
                    ${getEmptyStateIfNeeded(host)}
                </tbody>
                <tfoot class="link-field-table-footer">
                    <tr class="link-field-table-row">
                        <td class="link-field-table-footer-type">
                            ${getTypeSelectorTemplate(host)}
                        </td>
                        <td class="link-field-table-footer-input" colspan="3">
                            <div class="link-field-selector-wrapper">
                                <select data-select="artifact-link-select"></select>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    `,
});
