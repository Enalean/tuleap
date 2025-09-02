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
import "./tlp-layout.scss";

type LayoutProps = {
    content: string;
    frame: string;
    centered: boolean;
};

function getClasses(args: LayoutProps): string[] {
    const classes = [];
    if (args.frame.includes("framed")) {
        classes.push(`tlp-${args.frame}`);
    }
    if (args.centered) {
        classes.push("tlp-centered");
    }
    return classes;
}

function getTemplate(args: LayoutProps): TemplateResult {
    // prettier-ignore
    return html`
<div class="container">
    <main class="${getClasses(args).join(" ")}">${args.content}</main>
</div>`;
}

const meta: Meta<LayoutProps> = {
    title: "TLP/Structure & Navigation/Layout",
    parameters: {
        source: {
            excludeDecorators: true,
        },
        layout: "none",
    },
    render: (args: LayoutProps) => {
        return getTemplate(args);
    },
    argTypes: {
        content: {
            name: "Content",
        },
        frame: {
            name: "Frame",
            description:
                "Use .tlp-framed-vertically or .tlp-framed-horizontally if you need to add margin only at the top/bottom or to the left/right of the container.",
            options: ["framed", "framed-vertically", "framed-horizontally", "No frame"],
            control: {
                type: "select",
            },
            table: {
                type: { summary: ".tlp-framed" },
            },
        },
        centered: {
            name: "Centered",
            description: `In order to center content, you can use`,
            table: {
                type: { summary: ".tlp-centered" },
            },
        },
    },
    args: {
        content: "Content",
        frame: "framed",
    },
};
export default meta;
type Story = StoryObj<LayoutProps>;

export const Layout: Story = {
    args: {
        centered: false,
    },
};
