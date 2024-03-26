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

import type { Meta, StoryObj } from "@storybook/web-components";
import { html } from "lit";

type CardProps = {
    content: string;
    selectable: boolean;
    inactive: boolean;
    hypertext: boolean;
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

const meta: Meta<CardProps> = {
    title: "TLP/Structure & Navigation/Cards",
    render: (args: CardProps) => {
        return html`${args.hypertext
            ? html`<a class="${getClasses(args)}">${args.content}</a>`
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
