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

function getTemplate(): TemplateResult {
    // prettier-ignore
    return html`
<svg
    width="316"
    viewBox="0 0 316 219"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
>
    <g clip-path="url(#clip0)">
        <path
            d="M316.5 158.25C316.5 245.649 245.649 316.5 158.25 316.5C70.8509 316.5 0 245.649 0 158.25C0 70.8509 70.8509 0 158.25 0C245.649 0 316.5 70.8509 316.5 158.25Z"
            fill="url(#paint0_linear)"
        />
        <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M93.711 81C90.0046 81 87 84.0046 87 87.711V96.789C87 100.495 90.0046 103.5 93.711 103.5H222.039C225.745 103.5 228.75 100.495 228.75 96.789V87.711C228.75 84.0046 225.745 81 222.039 81H93.711ZM96 113.74C96 110.57 98.5698 108 101.74 108H214.01C217.18 108 219.75 110.57 219.75 113.74V203.51C219.75 206.68 217.18 209.25 214.01 209.25H101.74C98.5698 209.25 96 206.68 96 203.51V113.74ZM134.25 124.875C134.25 121.768 136.768 119.25 139.875 119.25H175.875C178.982 119.25 181.5 121.768 181.5 124.875C181.5 127.982 178.982 130.5 175.875 130.5H139.875C136.768 130.5 134.25 127.982 134.25 124.875Z"
            fill="var(--tlp-illustration-main-color)"
        />
        <rect
            x="112.5"
            y="149.25"
            width="90"
            height="46.5"
            rx="3.62264"
            fill="var(--tlp-illustration-grey-on-white)"
        />
        <line
            x1="153"
            y1="161.25"
            x2="193.5"
            y2="161.25"
            stroke="var(--tlp-illustration-main-color)"
            stroke-width="3"
            stroke-linecap="round"
        />
        <line
            x1="153"
            y1="168.75"
            x2="178.5"
            y2="168.75"
            stroke="var(--tlp-illustration-main-color)"
            stroke-width="3"
            stroke-linecap="round"
        />
        <line
            x1="153"
            y1="176.25"
            x2="186"
            y2="176.25"
            stroke="var(--tlp-illustration-main-color)"
            stroke-width="3"
            stroke-linecap="round"
        />
        <line
            x1="153"
            y1="183.75"
            x2="178.5"
            y2="183.75"
            stroke="var(--tlp-illustration-main-color)"
            stroke-width="3"
            stroke-linecap="round"
        />
        <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M132.75 185.25C139.792 185.25 145.5 179.542 145.5 172.5C145.5 165.458 139.792 159.75 132.75 159.75C125.708 159.75 120 165.458 120 172.5C120 179.542 125.708 185.25 132.75 185.25ZM136.312 171H136.875C137.484 171 138 171.516 138 172.125V176.625C138 177.258 137.484 177.75 136.875 177.75H128.625C127.992 177.75 127.5 177.258 127.5 176.625V172.125C127.5 171.516 127.992 171 128.625 171H129.188V169.312C129.188 167.367 130.781 165.75 132.75 165.75C134.695 165.75 136.312 167.367 136.312 169.312V171ZM131.062 169.312V171H134.438V169.312C134.438 168.398 133.664 167.625 132.75 167.625C131.812 167.625 131.062 168.398 131.062 169.312Z"
            fill="var(--tlp-illustration-red)"
        />
        <line
            x1="80.5"
            y1="213.5"
            x2="235.5"
            y2="213.5"
            stroke="var(--tlp-illustration-main-color)"
            stroke-linecap="round"
        />
    </g>
    <defs>
        <linearGradient
            id="paint0_linear"
            x1="158.25"
            y1="0"
            x2="158.25"
            y2="187.5"
            gradientUnits="userSpaceOnUse"
        >
            <stop offset="0.416" stop-color="var(--tlp-illustration-grey-on-background)" />
            <stop
                offset="1"
                stop-color="var(--tlp-illustration-grey-on-background)"
                stop-opacity="0"
            />
        </linearGradient>
        <clipPath id="clip0">
            <rect width="316" height="219" fill="white" />
        </clipPath>
    </defs>
</svg>
`
}

const meta: Meta = {
    title: "TLP/Visual assets/Illustrations",
    render: () => {
        return getTemplate();
    },
};
export default meta;
type Story = StoryObj;

export const Illustration: Story = {};
