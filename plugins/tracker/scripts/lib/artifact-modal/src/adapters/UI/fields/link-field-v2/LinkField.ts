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
import { sprintf } from "sprintf-js";

import {
    getAddLinkButtonLabel,
    getLinkFieldFetchErrorMessage,
    getLinkFieldTableEmptyStateText,
    getLinkFieldUnderConstructionPlaceholder,
} from "../../../../gettext-catalog";
import type { LinkFieldControllerType } from "./LinkFieldController";
import { LinkFieldPresenter } from "./LinkFieldPresenter";
import { getLinkedArtifactTemplate } from "./LinkedArtifactTemplate";

export interface LinkField {
    readonly fieldId: number;
    readonly label: string;
    readonly content: () => HTMLElement;
    readonly controller: LinkFieldControllerType;
    presenter: LinkFieldPresenter;
}
export type HostElement = LinkField & HTMLElement;

type MapOfClasses = Record<string, boolean>;

const getLinksTableClasses = (presenter: LinkFieldPresenter): MapOfClasses => ({
    "tlp-table": true,
    "tuleap-artifact-modal-link-field-empty":
        presenter.has_loaded_content && presenter.error_message !== "",
});

const getInputRowNatureCellClasses = (presenter: LinkFieldPresenter): MapOfClasses => ({
    "link-field-table-footer-type": true,
    "link-field-table-footer-type-no-padding": presenter.linked_artifacts.length === 0,
});

const formatErrorMessage = (error_message: string): string =>
    sprintf(getLinkFieldFetchErrorMessage(), error_message);

export const getEmptyStateIfNeeded = (presenter: LinkFieldPresenter): UpdateFunction<LinkField> => {
    if (presenter.linked_artifacts.length > 0 || !presenter.has_loaded_content) {
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

const getFormattedArtifacts = (presenter: LinkFieldPresenter): UpdateFunction<LinkField>[] =>
    presenter.linked_artifacts.map(getLinkedArtifactTemplate);

export const getSkeletonIfNeeded = (presenter: LinkFieldPresenter): UpdateFunction<LinkField> => {
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

export const LinkField = define<LinkField>({
    tag: "tuleap-artifact-modal-link-field-v2",
    fieldId: 0,
    label: "",
    controller: {
        set(host, controller: LinkFieldControllerType) {
            controller.displayLinkedArtifacts().then((presenter) => (host.presenter = presenter));
            return controller;
        },
    },
    presenter: {
        get: (host, last_value) => last_value ?? LinkFieldPresenter.buildLoadingState(),
        set: (host, presenter) => presenter,
    },
    content: (host) => html`
        <label for="${"tracker_field_" + host.fieldId}" class="tlp-label">${host.label}</label>
        ${host.presenter.error_message !== "" &&
        html`
            <div
                id="tuleap-artifact-modal-link-error"
                class="tlp-alert-danger"
                data-test="linked-artifacts-error"
            >
                ${formatErrorMessage(host.presenter.error_message)}
            </div>
        `}
        <table
            id="tuleap-artifact-modal-link-table"
            class="${getLinksTableClasses(host.presenter)}"
            data-test="linked-artifacts-table"
        >
            <tbody class="link-field-table-body">
                ${getFormattedArtifacts(host.presenter)} ${getSkeletonIfNeeded(host.presenter)}
                ${getEmptyStateIfNeeded(host.presenter)}
            </tbody>
            <tfoot class="link-field-table-footer">
                <tr class="link-field-table-row">
                    <td class="${getInputRowNatureCellClasses(host.presenter)}"></td>
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
