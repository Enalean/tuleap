/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Meta, StoryObj } from "@storybook/web-components-vite";
import { html, type TemplateResult } from "lit";

type SkeletonTextProps = {
    example_displayed: string;
};

function getTypographyCase(): TemplateResult {
    // prettier-ignore
    return html`
<h1><span class="tlp-skeleton-text"></span></h1>
<h2><span class="tlp-skeleton-text"></span></h2>
<h3><span class="tlp-skeleton-text"></span></h3>
<h3><span class="tlp-skeleton-text" style="width: 400px"></span></h3>

<p>
    <span class="tlp-skeleton-text"></span>
    <span class="tlp-skeleton-text"></span>
</p>

<p>
    <i class="fa-solid fa-folder tlp-skeleton-text-icon tlp-skeleton-icon" aria-hidden="true"></i>
    <span class="tlp-skeleton-text"></span>
</p>`;
}

function getFieldsCase(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-form-element">
    <label class="tlp-label tlp-skeleton-text"></label>
    <input type="text" class="tlp-input tlp-skeleton-field" disabled>
</div>
<div class="tlp-form-element">
    <label class="tlp-label tlp-skeleton-text"></label>
    <input type="text" class="tlp-input tlp-input-large tlp-skeleton-field" disabled>
</div>
<div class="tlp-form-element">
    <label class="tlp-label tlp-skeleton-text"></label>
    <select class="tlp-select tlp-skeleton-field" disabled></select>
</div>
<div class="tlp-form-element">
    <label class="tlp-label tlp-checkbox">
        <input type="checkbox" class="tlp-skeleton-field" disabled> <span class="tlp-skeleton-text"></span>
    </label>
</div>`;
}

function getCardsCase(): TemplateResult {
    // prettier-ignore
    return html`
<div class="tlp-card tlp-skeleton-card">
</div>
<div class="tlp-card tlp-skeleton-card">
</div>`;
}

function getInsideTablesCase(): TemplateResult {
    // prettier-ignore
    return html`
<table class="tlp-table" id="my-table">
    <thead>
    <tr>
        <th>One</th>
        <th>Two three four five</th>
        <th>Six</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td><span class="tlp-skeleton-text"></span></td>
            <td>
                <i class="fa-solid fa-folder tlp-skeleton-icon" aria-hidden="true"></i>
                <span class="tlp-skeleton-text"></span>
            </td>
            <td><span class="tlp-skeleton-text"></span></td>
        </tr>
        <tr>
            <td><span class="tlp-skeleton-text"></span></td>
            <td>
                <i class="fa-solid fa-folder tlp-skeleton-text-icon tlp-skeleton-icon" aria-hidden="true"></i>
                <span class="tlp-skeleton-text"></span>
            </td>
            <td><span class="tlp-skeleton-text"></span></td>
        </tr>
        <tr>
            <td><span class="tlp-skeleton-text"></span></td>
            <td>
                <i class="fa-solid fa-folder tlp-skeleton-text-icon tlp-skeleton-icon" aria-hidden="true"></i>
                <span class="tlp-skeleton-text"></span>
            </td>
            <td><span class="tlp-skeleton-text"></span></td>
        </tr>
    </tbody>
</table>`;
}

const meta: Meta<SkeletonTextProps> = {
    title: "TLP/Visual assets/Skeleton screens",
    parameters: {
        controls: {
            exclude: ["example_displayed"],
        },
        layout: "padded",
    },
    render: (args): TemplateResult => {
        switch (args.example_displayed) {
            default:
                return html``;
            case "for typography":
                return getTypographyCase();
            case "for fields":
                return getFieldsCase();
            case "for cards":
                return getCardsCase();
            case "in table":
                return getInsideTablesCase();
        }
    },
};

export default meta;
type Story = StoryObj<SkeletonTextProps>;

export const SkeletonForTypography: Story = {
    args: {
        example_displayed: "for typography",
    },
};

export const SkeletonForFields: Story = {
    args: {
        example_displayed: "for fields",
    },
};

export const SkeletonForCards: Story = {
    args: {
        example_displayed: "for cards",
    },
};

export const SkeletonInsideTables: Story = {
    args: {
        example_displayed: "in table",
    },
};
