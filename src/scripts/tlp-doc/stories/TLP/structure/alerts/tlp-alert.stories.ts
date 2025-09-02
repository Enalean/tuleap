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
import { USER_INTERFACE_COLORS } from "@tuleap/core-constants";
import type { UserInterfaceColorName } from "@tuleap/core-constants";

type AlertProps = {
    type: UserInterfaceColorName;
    content: string;
    with_title: boolean;
    title: string;
};

function getClasses(args: AlertProps): string[] {
    return [`tlp-alert-${args.type}`];
}

function getTemplate(args: AlertProps): TemplateResult {
    // prettier-ignore
    return html`
<div class="${getClasses(args).join(" ")}">${args.with_title ? html`
    <p class="tlp-alert-title">${args.title}</p>` : ``}${args.content}
</div>`;
}

const meta: Meta<AlertProps> = {
    title: "TLP/Structure & Navigation/Alerts",
    render: (args) => {
        return getTemplate(args);
    },
    argTypes: {
        type: {
            name: "Type",
            description: "UI color of the alert",
            options: USER_INTERFACE_COLORS,
            control: {
                type: "select",
            },
            table: {
                type: { summary: undefined },
            },
        },
        content: {
            name: "Content",
        },
        with_title: {
            name: "With title",
            description: "Add a title with the class",
            table: {
                type: { summary: ".tlp-alert-title" },
            },
        },
        title: {
            name: "Title",
        },
    },
    args: {
        type: "info",
        content: "This alert is customizable",
        with_title: true,
        title: "Title",
    },
};

export default meta;
type Story = StoryObj<AlertProps>;

export const Alert: Story = {};

export const InfoAlert: Story = {
    args: {
        type: "info",
        content: "This alert needs your attention, but it's not super important.",
        title: "Heads up!",
    },
};

export const SuccessAlert: Story = {
    args: {
        type: "success",
        content: "You successfully read this important alert message.",
        title: "Well done!",
    },
};

export const WarningAlert: Story = {
    args: {
        type: "warning",
        content: "Better check yourself, you're not looking too good.",
        title: "Warning!",
    },
};

export const DangerAlert: Story = {
    args: {
        type: "danger",
        content: "Change a few things up and try submitting again.",
        title: "Oh snap!",
    },
};
