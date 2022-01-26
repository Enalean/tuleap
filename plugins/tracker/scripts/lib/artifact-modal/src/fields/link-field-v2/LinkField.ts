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

import { define, html } from "hybrids";
import { sprintf } from "sprintf-js";

import {
    getLinkFieldUnderConstructionPlaceholder,
    getLinkFieldFetchErrorMessage,
    getAddLinkButtonLabel,
    getLinkFieldTableEmptyStateText,
} from "../../gettext-catalog";
import { isInCreationMode } from "../../modal-creation-mode-state";
import { getLinkedArtifacts } from "./links-retriever";

import type { UpdateFunction } from "hybrids";
import type { LinkedArtifact } from "./links-retriever";

export interface LinkField {
    readonly fieldId: number;
    readonly label: string;
    readonly artifactId: number;
    readonly content: () => HTMLElement;
    linked_artifacts: Array<LinkedArtifact>;
    has_loaded_content: boolean;
    is_loading: boolean;
    error_message: string;
}

type MapOfClasses = Record<string, boolean>;

export type HostElement = LinkField & HTMLElement;

export const getLinksTableClasses = (host: LinkField): MapOfClasses => ({
    "tlp-table": true,
    "tuleap-artifact-modal-link-field-empty": host.has_loaded_content && host.error_message !== "",
});

export const getArtifactsStatusBadgeClasses = (artifact: LinkedArtifact): MapOfClasses => ({
    "tlp-badge-outline": true,
    "tlp-badge-success": artifact.is_open,
    "tlp-badge-secondary": !artifact.is_open,
});

export const getArtifactTableRowClasses = (artifact: LinkedArtifact): MapOfClasses => ({
    "link-field-table-row": true,
    "link-field-table-row-muted": artifact.status !== "" && !artifact.is_open,
});

export const getInputRowNatureCellClasses = (host: LinkField): MapOfClasses => ({
    "link-field-table-footer-type": true,
    "link-field-table-footer-type-no-padding": host.linked_artifacts.length === 0,
});

const formatErrorMessage = (error: Error): string =>
    sprintf(getLinkFieldFetchErrorMessage(), error.message);

export const getEmptyStateIfNeeded = (host: LinkField): UpdateFunction<LinkField> => {
    if (host.linked_artifacts.length > 0 || !host.has_loaded_content) {
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

export const getFormattedArtifacts = (host: LinkField): UpdateFunction<LinkField>[] =>
    host.linked_artifacts.map(
        (artifact: LinkedArtifact) => html`
            <tr class="${getArtifactTableRowClasses(artifact)}" data-test="artifact-row">
                <td class="link-field-table-cell-type">${artifact.link_type.label}</td>
                <td class="link-field-table-cell-xref">
                    <a
                        href="${artifact.html_url}"
                        class="link-field-artifact-link"
                        data-test="artifact-link"
                    >
                        <span
                            class="
                                cross-ref-badge
                                cross-ref-badge-${artifact.tracker.color_name}
                                link-field-xref-badge
                            "
                            data-test="artifact-xref"
                        >
                            ${artifact.xref}
                        </span>
                        <span class="link-field-artifact-title" data-test="artifact-title">
                            ${artifact.title}
                        </span>
                    </a>
                </td>
                <td class="link-field-table-cell-status">
                    ${artifact.status &&
                    html`
                        <span
                            class="${getArtifactsStatusBadgeClasses(artifact)}"
                            data-test="artifact-status"
                        >
                            ${artifact.status}
                        </span>
                    `}
                </td>
                <td class="link-field-table-cell-action"></td>
            </tr>
        `
    );

export const retrieveLinkedArtifacts = (host: LinkField): Promise<void> => {
    if (isInCreationMode()) {
        host.has_loaded_content = true;

        return Promise.resolve();
    }

    host.is_loading = true;
    return getLinkedArtifacts(host.artifactId)
        .then((artifacts) => {
            host.linked_artifacts = artifacts;
        })
        .catch((err: Error) => {
            host.error_message = formatErrorMessage(err);
        })
        .finally(() => {
            host.is_loading = false;
            host.has_loaded_content = true;
        });
};

export const getSkeletonIfNeeded = (host: LinkField): UpdateFunction<LinkField> => {
    if (!host.is_loading) {
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

export const LinkField = define<LinkField>({
    tag: "tuleap-artifact-modal-link-field-v2",
    fieldId: 0,
    label: "",
    artifactId: {
        value: 0,
        observe: retrieveLinkedArtifacts,
    },
    has_loaded_content: false,
    is_loading: false,
    error_message: "",
    linked_artifacts: {
        get: (host, value = []) => value,
        set: (host, value = []) => [...value],
    },
    content: (host) => html`
        <label for="${"tracker_field_" + host.fieldId}" class="tlp-label">${host.label}</label>
        ${host.error_message !== "" &&
        html`
            <div
                id="tuleap-artifact-modal-link-error"
                class="tlp-alert-danger"
                data-test="linked-artifacts-error"
            >
                ${host.error_message}
            </div>
        `}
        <table
            id="tuleap-artifact-modal-link-table"
            class="${getLinksTableClasses(host)}"
            data-test="linked-artifacts-table"
        >
            <tbody class="link-field-table-body">
                ${getFormattedArtifacts(host)} ${getSkeletonIfNeeded(host)}
                ${getEmptyStateIfNeeded(host)}
            </tbody>
            <tfoot class="link-field-table-footer">
                <tr class="link-field-table-row">
                    <td class="${getInputRowNatureCellClasses(host)}"></td>
                    <td class="link-field-table-footer-input" colspan="2">
                        <input
                            id="${"tracker_field_" + host.fieldId}"
                            type="text"
                            class="tlp-input tlp-input-small"
                            placeholder="${getLinkFieldUnderConstructionPlaceholder()}"
                        />
                    </td>
                    <td class="link-field-table-footer-add-link">
                        <button type="button" class="tlp-button-small tlp-button-primary" disabled>
                            ${getAddLinkButtonLabel()}
                        </button>
                    </td>
                </tr>
            </tfoot>
        </table>
    `,
});
