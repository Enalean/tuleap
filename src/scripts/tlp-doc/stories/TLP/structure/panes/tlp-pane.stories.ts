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

type PaneProps = {
    title?: string;
    title_with_icon?: boolean;
    content?: string;
    tabs?: boolean;
    table?: boolean;
    submit_button?: boolean;
    split?: "No splitting" | "Horizontally" | "Vertically" | "Both";
    story?: "tabs" | "table" | "submit" | "submit in new section" | "horizontal" | "vertical";
};

const DEFAULT_TITLE = "Pane title";
const DEFAULT_CONTENT =
    "The content of the pane goes here. The pane can have more than one section.";

function getTemplate(args: PaneProps): TemplateResult {
    const content = args.content ?? DEFAULT_CONTENT;
    const title = args.title ?? DEFAULT_TITLE;

    const table = args.story === "table" || (args.story === undefined && args.table);
    const tabs = args.story === "tabs" || (args.story === undefined && args.tabs);
    const submit_button =
        args.story === "submit" || (args.story === undefined && args.submit_button);
    const submit_button_in_new_section = args.story === "submit in new section";
    const split: PaneProps["split"] =
        args.story === "horizontal"
            ? "Horizontally"
            : args.story === "vertical"
              ? "Vertically"
              : args.story === undefined
                ? args.split
                : "No splitting";

    // prettier-ignore
    return html`
<section class="tlp-pane">
    <div class="tlp-pane-container">
        <div class="tlp-pane-header">
            <h1 class="tlp-pane-title">${args.title_with_icon ? html`
                <i class="tlp-pane-title-icon fa-solid fa-list"
                   aria-hidden="true"></i>` : ``}${title}
            </h1>
        </div>${tabs ? html`
        <nav class="tlp-tabs">
              <a href="https://example.com" class="tlp-tab">Overview</a>
              <a href="https://example.com" class="tlp-tab tlp-tab-active">Comments</a>
              <a href="https://example.com" class="tlp-tab">History</a>
        </nav>` : ``}
        <section class="tlp-pane-section">
            <p>${content}</p>${table ? html`
            <table class="tlp-table">
                  <thead>
                      <tr>
                          <th>Firstname</th>
                          <th>Lastname</th>
                          <th>Status</th>
                      </tr>
                  </thead>
                  <tbody>
                      <tr>
                          <td>Allen</td>
                          <td>Woody</td>
                          <td>
                              <span class="tlp-badge-success tlp-badge-outline"
                                  >Active</span
                              >
                          </td>
                      </tr>
                      <tr>
                          <td>George</td>
                          <td>Harrison</td>
                          <td>
                              <span class="tlp-badge-danger tlp-badge-outline"
                                  >Deleted</span
                              >
                          </td>
                      </tr>
                      <tr>
                          <td>Graam</td>
                          <td>Parson</td>
                          <td>
                              <span class="tlp-badge-success tlp-badge-outline"
                                  >Active</span
                              >
                          </td>
                      </tr>
                  </tbody>
            </table>` : ""}${submit_button ? html`
            <div class="tlp-pane-section-submit">
                  <button type="submit" class="tlp-button-primary">Submit</button>
            </div>` : ""}
        </section>${submit_button_in_new_section ? html`
        <section class="tlp-pane-section tlp-pane-section-submit">
            <button type="submit" class="tlp-button-primary">Submit</button>
        </section>` : ""}${split === "Horizontally" || split === "Both" ? html`
        <section class="tlp-pane-section">
              <p>${content}</p>
        </section>` : ``}
    </div>${split === "Vertically" || split === "Both" ? html`
    <div class="tlp-pane-container">
        <section class="tlp-pane-section">
            <p>${content}</p>
        </section>
    </div>` : ``}
</section>`;
}

const meta: Meta<PaneProps> = {
    title: "TLP/Structure & Navigation/Panes",
    parameters: {
        layout: "padded",
        controls: {
            exclude: ["story"],
        },
    },
    render: (args) => {
        return getTemplate(args);
    },
};
export default meta;
type Story = StoryObj<PaneProps>;

export const Pane: Story = {
    args: {
        title: DEFAULT_TITLE,
        title_with_icon: false,
        content: DEFAULT_CONTENT,
        tabs: false,
        table: false,
        submit_button: false,
        split: "No splitting",
    },
    argTypes: {
        title: {
            name: "Title",
        },
        title_with_icon: {
            name: "Title with icon",
            description: "Add an icon to the title with the appropriate class",
            table: {
                type: { summary: ".tlp-pane-title-icon" },
            },
        },
        content: {
            name: "Content",
            description: "Content of the pane",
        },
        split: {
            name: "Splitting",
            description: "Select to split the pane horizontally, vertically or both",
            options: ["No splitting", "Horizontally", "Vertically", "Both"],
            control: {
                type: "select",
            },
            table: {
                type: { summary: undefined },
            },
        },
        tabs: {
            name: "With tabs",
            description: "Add an example of tabs",
            table: {
                type: { summary: undefined },
            },
        },
        table: {
            name: "With table",
            description: "Add an example of table",
            table: {
                type: { summary: undefined },
            },
        },
        submit_button: {
            name: "With submit button",
            description: "Add an example of submit button",
            table: {
                type: { summary: undefined },
            },
        },
    },
};

export const PaneWithTabs: Story = {
    args: {
        story: "tabs",
    },
};

export const PaneWithTable: Story = {
    args: {
        story: "table",
    },
};

export const PaneWithSubmitButton: Story = {
    args: {
        story: "submit",
    },
};

export const PaneWithSubmitButtonInNewSection: Story = {
    args: {
        story: "submit in new section",
    },
};

export const HorizontallySplitPane: Story = {
    args: {
        story: "horizontal",
    },
};

export const VerticallySplitPane: Story = {
    args: {
        story: "vertical",
    },
};
