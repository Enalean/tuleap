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
import type { TemplateResult } from "lit";
import { html } from "lit";

type PaneOption = "pane-with-title" | "pane-without-title" | "pane-with-tabs";

type CardProps = {
    content: string;
    selectable: boolean;
    inactive: boolean;
    hypertext: boolean;
    pane: PaneOption | null;
};

function getClasses(args: CardProps): string {
    const classes = ["tlp-card"];
    if (args.selectable) {
        classes.push("tlp-card-selectable");
    }
    if (args.inactive) {
        classes.push("tlp-card-inactive");
    }
    return classes.join(" ");
}

function getCardInPane(pane: PaneOption): TemplateResult {
    if (pane === "pane-with-title") {
        return getCardInPaneWithTitle();
    } else if (pane === "pane-with-tabs") {
        return getCardInPaneWithTabs();
    }
    return getCardInPaneWithoutTitle();
}

function getCardInPaneWithTitle(): TemplateResult {
    // prettier-ignore
    return html`
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <i class="fa-solid fa-hand-peace tlp-pane-title-icon" aria-hidden="true"></i> A pane with cards
            </h1>
        </div>
        <section class="tlp-pane-section-for-cards">
            <a class="tlp-card tlp-card-selectable" href="https://example.com">
                This is the content of My card.
            </a>
            <a class="tlp-card tlp-card-selectable" href="https://example.com">
                This is the content of My card.
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is a closed card.</p>
            </a>
            <div class="tlp-card tlp-card-selectable">
                And this is the content of another card.
            </div>
            <div class="tlp-card tlp-card-selectable">
                And this is the content of another card.
            </div>
            <div class="tlp-card tlp-card-selectable">
                And this is the content of another card.
            </div>
        </section>
    </div>
</section>`;
}

function getCardInPaneWithTabs(): TemplateResult {
    // prettier-ignore
    return html`
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">
                <i class="fa-solid fa-hand-spock tlp-pane-title-icon" aria-hidden="true"></i> A pane with cards under tabs
            </h1>
        </div>
        <nav class="tlp-tabs">
            <a href="https://example.com" class="tlp-tab">Overview</a>
            <a href="https://example.com" class="tlp-tab tlp-tab-active">Comments</a>
            <a href="https://example.com" class="tlp-tab">History</a>
        </nav>
        <section class="tlp-pane-section-for-cards-under-tabs">
            <a class="tlp-card tlp-card-selectable" href="https://example.com">
                This is the content of My card.
            </a>
            <a class="tlp-card tlp-card-selectable" href="https://example.com">
                This is the content of My card.
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is an inactive card.</p>
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is an inactive card.</p>
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is an inactive card.</p>
            </a>
            <div class="tlp-card tlp-card-selectable">
                And this is the content of another card.
            </div>
        </section>
    </div>
</section>`;
}

function getCardInPaneWithoutTitle(): TemplateResult {
    // prettier-ignore
    return html`
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <section class="tlp-pane-section-for-cards">
            <a class="tlp-card tlp-card-selectable" href="https://example.com">
                This is the content of My card.
            </a>
            <a class="tlp-card tlp-card-selectable" href="https://example.com">
                This is the content of My card.
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is an inactive card.</p>
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is an inactive card.</p>
            </a>
            <a class="tlp-card tlp-card-inactive tlp-card-selectable" href="https://example.com">
                <p class="tlp-text-muted">This is an inactive card.</p>
            </a>
            <div class="tlp-card tlp-card-selectable">
                And this is the content of another card.
            </div>
        </section>
    </div>
</section>`;
}

const meta: Meta<CardProps> = {
    title: "TLP/Structure & Navigation/Cards",
    render: (args: CardProps) => {
        if (args.pane) {
            return getCardInPane(args.pane);
        }

        return html`${args.hypertext
            ? html`<a class="${getClasses(args)}" href="https://example.com">${args.content}</a>`
            : html`<div class="${getClasses(args)}">${args.content}</div>`}`;
    },
    args: {
        content: "This is the content of my card.",
        selectable: false,
        inactive: false,
        hypertext: false,
    },
    argTypes: {
        content: {
            name: "Content",
        },
        hypertext: {
            name: "Hypertext",
            description:
                "It is recommended to use a &lt;a&gt; element, so that end user can use the whole card as a regular link (open in new tab, copy link location, …) which is not possible with a &lt;div&gt; element.",
        },
        selectable: {
            name: "Selectable",
            description:
                "Most of the time you will allow the user to select a card among others. This is done by using the class",
            table: {
                type: { summary: ".tlp-card-selectable" },
            },
        },
        inactive: {
            name: "Inactive",
            description: "Applies the class",
            table: {
                type: { summary: ".tlp-card-inactive" },
            },
        },
    },
};
export default meta;
type Story = StoryObj<CardProps>;

export const Card: Story = {};

export const InPaneWithTitle: Story = {
    args: {
        pane: "pane-with-title",
    },
    argTypes: {
        content: { control: false },
        hypertext: { control: false },
        selectable: { control: false },
        inactive: { control: false },
        pane: { control: false },
    },
};

export const InPaneWithoutTitle: Story = {
    args: {
        pane: "pane-without-title",
    },
    argTypes: {
        content: { control: false },
        hypertext: { control: false },
        selectable: { control: false },
        inactive: { control: false },
        pane: { control: false },
    },
};

export const InPaneWithTabs: Story = {
    args: {
        pane: "pane-with-tabs",
    },
    argTypes: {
        content: { control: false },
        hypertext: { control: false },
        selectable: { control: false },
        inactive: { control: false },
        pane: { control: false },
    },
};
