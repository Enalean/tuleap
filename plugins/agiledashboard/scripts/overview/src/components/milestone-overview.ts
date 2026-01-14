/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import type { GetText } from "@tuleap/gettext";
import type { Artifact } from "../types";
import type { UpdateFunction } from "hybrids";
import { define, html } from "hybrids";

export const TAG = "tuleap-backlog-milestone-overview";

type CSRFToken = {
    readonly name: string;
    readonly token: string;
};

type MilestoneOverview = {
    gettext_provider: GetText;
    solve_inconsistencies_url: string;
    csrf_token: CSRFToken;
    all_linked_items: ReadonlyArray<Artifact>;
    inconsistent_items: ReadonlyArray<Artifact>;
};

type InternalMilestoneOverview = MilestoneOverview & {
    has_item: boolean;
    has_inconsistent_item: boolean;
};

type HostElement = InternalMilestoneOverview & HTMLElement;

function renderInconsistentMessage(host: HostElement): UpdateFunction<HostElement> {
    if (!host.has_inconsistent_item) {
        return html``;
    }

    return html`
        <form method="POST" action="${host.solve_inconsistencies_url}" class="tlp-alert-warning">
            <input type="hidden" name="${host.csrf_token.name}" value="${host.csrf_token.token}" />
            ${host.inconsistent_items.map(
                (item) =>
                    html`<input
                        type="hidden"
                        name="inconsistent-artifacts-ids[]"
                        value="${item.id}"
                        data-test="inconsistent-input"
                    />`,
            )}
            ${host.gettext_provider.gettext("Some items are not linked to this milestone.")}
            <input
                type="submit"
                class="tlp-button-warning tlp-button-small"
                value="${host.gettext_provider.gettext("Import them in the backlog")}"
            />
        </form>
    `;
}

function renderItemRow(host: HostElement, item: Artifact): UpdateFunction<HostElement> {
    const is_inconsistent =
        host.inconsistent_items.find((inconsistent_item) => inconsistent_item.id === item.id) !==
        undefined;
    const inconsistent_label = host.gettext_provider.gettext(
        "Item is not linked to this milestone",
    );

    return html`
        <tr data-test="artifact-row">
            <td
                ${item.status !== "Open" &&
                'class="agiledashboard-milestone-details-items-list-is-closed"'}
                data-test="item-label"
            >
                <a href="/plugins/tracker/?aid=${item.id}">
                    <span
                        class="agiledashboard-milestone-details-items-list-badge cross-ref-badge tlp-swatch-${item.color}"
                        >${item.short_type} #${item.id}</span
                    >${item.label}
                </a>
            </td>
            <td
                ${item.status !== "Open" &&
                'class="agiledashboard-milestone-details-items-list-is-closed"'}
            >
                ${is_inconsistent &&
                html`
                    <span
                        class="tlp-tooltip tlp-tooltip-top"
                        data-tlp-tooltip="${inconsistent_label}"
                        data-test="inconsistent-warning"
                    >
                        <i
                            class="tlp-text-warning fa-solid fa-triangle-exclamation"
                            aria-label="${inconsistent_label}"
                        ></i>
                    </span>
                `}
            </td>
            <td
                ${item.status !== "Open" &&
                'class="agiledashboard-milestone-details-items-list-is-closed"'}
                data-test="item-status"
            >
                ${item.full_status.value}
            </td>
            <td
                class="milestone-element-parent ${item.status !== "Open" &&
                "agiledashboard-milestone-details-items-list-is-closed"}"
            >
                ${item.parent !== null &&
                html`<a href="/plugins/tracker/?aid=${item.parent.id}">${item.parent.label}</a>`}
            </td>
        </tr>
    `;
}

function renderItemTable(host: HostElement): UpdateFunction<HostElement> {
    return html`
        <table class="tlp-table" data-test="release-overview">
            <thead>
                <tr>
                    <th>${host.gettext_provider.gettext("Item")}</th>
                    <th></th>
                    <th>${host.gettext_provider.gettext("Status")}</th>
                    <th>${host.gettext_provider.gettext("Parent item")}</th>
                </tr>
            </thead>
            <tbody>
                ${host.has_item
                    ? host.all_linked_items.map((item) => renderItemRow(host, item))
                    : html`
                          <tr>
                              <td colspan="4" class="tlp-table-cell-empty">
                                  ${host.gettext_provider.gettext("There is no item yet")}
                              </td>
                          </tr>
                      `}
            </tbody>
        </table>
    `;
}

define<InternalMilestoneOverview>({
    tag: TAG,
    gettext_provider: (_, value) => value,
    solve_inconsistencies_url: (_, value) => value,
    csrf_token: (_, value) => value,
    all_linked_items: (_, value) => value,
    inconsistent_items: (_, value) => value,
    has_item: (host) => host.all_linked_items.length > 0,
    has_inconsistent_item: (host) => host.inconsistent_items.length > 0,
    render: (host) => html`${renderInconsistentMessage(host)}${renderItemTable(host)}`,
});

export function isMilestoneOverviewElement(
    element: HTMLElement,
): element is MilestoneOverview & HTMLElement {
    return element.tagName === TAG.toUpperCase();
}
