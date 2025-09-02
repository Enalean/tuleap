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

type AvatarProps = {
    size: string;
    img: boolean;
    all_variations: boolean;
};

const avatar_image = new URL("../../../../images/random_avatar.jpg", import.meta.url).href;
const SIZES = ["jumbo", "extra-large", "large", "default", "medium", "small", "mini"];
const new_line = "\n";

function getClass(size: string): string {
    if (size === "default") {
        return "tlp-avatar";
    }
    return "tlp-avatar-" + size;
}

function avatarWithSelectedImage(size: string): TemplateResult {
    return html`<div class="${getClass(size)}">
        <img src="${avatar_image}" alt="User avatar" loading="lazy" />
    </div>`;
}

function defaultAvatar(size: string): TemplateResult {
    return html`<div class="${getClass(size)}"></div>`;
}

function avatar(size: string, with_image: boolean): TemplateResult {
    return with_image
        ? html`${avatarWithSelectedImage(size)}${new_line}`
        : html`${defaultAvatar(size)}${new_line}`;
}

function createAllVariations(args: AvatarProps): TemplateResult[] {
    return SIZES.map((size) => avatar(size, args.img));
}

const meta: Meta<AvatarProps> = {
    title: "TLP/Visual assets/Avatars",
    parameters: {
        controls: {
            exclude: ["all_variations"],
        },
    },
    render: (args) => {
        return args.all_variations
            ? html`${createAllVariations(args)}`
            : avatar(args.size, args.img);
    },
    args: {
        all_variations: false,
        img: false,
        size: "default",
    },
    argTypes: {
        size: {
            name: "Size",
            control: "select",
            if: { arg: "all_variations", truthy: false },
            options: SIZES,
            description: "Avatar size",
            table: {
                type: { summary: undefined },
            },
        },
        img: {
            name: "With image",
            description: "Example replacing the default avatar",
            table: {
                type: { summary: undefined },
            },
        },
    },
};

export default meta;
type Story = StoryObj<AvatarProps>;

export const Avatar: Story = {};

export const AllVariations: Story = {
    args: { all_variations: true },
};

export const AllVariationsWithImage: Story = {
    args: {
        all_variations: true,
        img: true,
    },
};
