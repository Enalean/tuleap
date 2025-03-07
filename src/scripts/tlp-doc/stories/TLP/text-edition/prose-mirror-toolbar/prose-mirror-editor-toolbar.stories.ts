/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Meta, StoryObj } from "@storybook/web-components";
import type { TemplateResult } from "lit";
import { html } from "lit";
import {
    buildToolbarController,
    createProseMirrorEditorToolbar,
    BASIC_TEXT_ITEMS_GROUP,
    LINK_ITEMS_GROUP,
    LIST_ITEMS_GROUP,
    SCRIPTS_ITEMS_GROUP,
    TEXT_STYLES_ITEMS_GROUP,
} from "@tuleap/prose-mirror-editor-toolbar";
import { getToolbarDemoBus, initToolbarDemo } from "./toolbar-interactions-emulation";
import type {
    LinkElements,
    ListElements,
    ScriptElements,
    StyleElements,
    TextElements,
    AdditionalElementPosition,
    ItemGroupName,
} from "@tuleap/prose-mirror-editor-toolbar";
import { buildCustomButton } from "./CustomButton";

const getTemplate = (args: ProseMirrorEditorToolbarProps): TemplateResult => {
    const toolbar_bus = getToolbarDemoBus();
    const controller = buildToolbarController(toolbar_bus);
    const toolbar = createProseMirrorEditorToolbar(document);

    toolbar.controller = controller;
    toolbar.text_elements = {
        bold: args.text_elements.includes("bold"),
        code: args.text_elements.includes("code"),
        quote: args.text_elements.includes("quote"),
        italic: args.text_elements.includes("italic"),
    };
    toolbar.script_elements = {
        subscript: args.script_elements.includes("subscript"),
        superscript: args.script_elements.includes("superscript"),
    };
    toolbar.link_elements = {
        link: args.link_elements.includes("link"),
        unlink: args.link_elements.includes("unlink"),
        image: args.link_elements.includes("image"),
    };
    toolbar.list_elements = {
        ordered_list: args.list_elements.includes("ordered_list"),
        bullet_list: args.list_elements.includes("bullet_list"),
    };
    toolbar.style_elements = {
        headings:
            args.style_elements.includes("headings") && !args.style_elements.includes("subtitles"),
        text: args.style_elements.includes("text"),
        preformatted: args.style_elements.includes("preformatted"),
        subtitles:
            args.style_elements.includes("subtitles") && !args.style_elements.includes("headings"),
    };

    toolbar.additional_elements = [
        {
            position: args.custom_button_position,
            target_name: args.custom_button_target_group_name,
            item_element: buildCustomButton(args.is_toolbar_disabled),
        },
    ];

    initToolbarDemo(toolbar_bus, args.is_toolbar_disabled);

    return html`${toolbar}`;
};

type ProseMirrorEditorToolbarProps = {
    text_elements: Array<keyof TextElements>;
    script_elements: Array<keyof ScriptElements>;
    link_elements: Array<keyof LinkElements>;
    list_elements: Array<keyof ListElements>;
    style_elements: Array<keyof StyleElements>;
    custom_button_position: AdditionalElementPosition;
    custom_button_target_group_name: ItemGroupName;
    is_toolbar_disabled: boolean;
};

const meta: Meta<ProseMirrorEditorToolbarProps> = {
    title: "TLP/Text Edition/Prose Mirror Toolbar",
    render: getTemplate,
    parameters: {
        docs: {
            story: {
                height: "350px",
            },
        },
    },
    args: {
        is_toolbar_disabled: false,
        custom_button_position: "at_the_end",
        custom_button_target_group_name: LINK_ITEMS_GROUP,
        text_elements: ["bold", "code", "quote", "italic"],
        style_elements: ["headings", "text", "preformatted"],
        list_elements: ["ordered_list", "bullet_list"],
        link_elements: ["link", "unlink", "image"],
        script_elements: ["subscript", "superscript"],
    },
    argTypes: {
        is_toolbar_disabled: {
            name: "is_toolbar_disabled",
            description: "Disable the whole toolbar",
            control: "boolean",
        },
        text_elements: {
            name: "text_elements",
            description: "Enable text buttons in the toolbar",
            control: "check",
            options: ["bold", "code", "quote", "italic"],
        },
        style_elements: {
            name: "style_elements",
            description: "Enable style options in the text-style select box",
            control: "check",
            options: ["headings", "subtitles", "text", "preformatted"],
        },
        list_elements: {
            name: "list_elements",
            description: "Enable list buttons in the toolbar",
            control: "check",
            options: ["ordered_list", "bullet_list"],
        },
        link_elements: {
            name: "link_elements",
            description: "Enable link buttons in the toolbar",
            control: "check",
            options: ["link", "unlink", "image"],
        },
        script_elements: {
            name: "script_elements",
            description: "Enable script buttons in the toolbar",
            control: "check",
            options: ["subscript", "superscript"],
        },
        custom_button_position: {
            name: "Custom button position in the toolbar",
            description: "Custom button position in the toolbar",
            control: "select",
            options: ["before", "after", "at_the_start", "at_the_end"],
        },
        custom_button_target_group_name: {
            name: "Custom button target group name",
            description: "The name of the target group of items",
            control: "select",
            options: [
                BASIC_TEXT_ITEMS_GROUP,
                LIST_ITEMS_GROUP,
                LINK_ITEMS_GROUP,
                SCRIPTS_ITEMS_GROUP,
                TEXT_STYLES_ITEMS_GROUP,
            ],
        },
    },
};

export default meta;
type Story = StoryObj<ProseMirrorEditorToolbarProps>;

export const ProseMirrorEditorToolbar: Story = {};
