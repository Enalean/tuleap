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

type PaneProps = {
    title: string;
    content: string;
    tabs: boolean;
    table: boolean;
    submit_button: boolean;
    split: string;
};

const meta: Meta<PaneProps> = {
    title: "TLP/Structure & Navigation/Panes",
    render: (args) => {
        return html`<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">
                        ${args.table
                            ? html`<i
                                  class="tlp-pane-title-icon fa-solid fa-list"
                                  aria-hidden="true"
                              ></i>`
                            : ``}${args.title}
                    </h1>
                </div>
                ${args.tabs
                    ? html` <nav class="tlp-tabs">
                          <a class="tlp-tab">Overview</a>
                          <a class="tlp-tab tlp-tab-active">Comments</a>
                          <a class="tlp-tab">History</a>
                      </nav>`
                    : ``}
                <section class="tlp-pane-section">
                    <p>${args.content}</p>
                    ${args.table
                        ? html`<table class="tlp-table">
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
                          </table>`
                        : ""}
                    ${args.submit_button
                        ? html`<div class="tlp-pane-section-submit">
                              <button type="submit" class="tlp-button-primary">Submit</button>
                          </div>`
                        : ""}
                </section>
                ${args.split === "Horizontally" || args.split === "Both"
                    ? html`<section class="tlp-pane-section">
                          <p>${args.content}</p>
                      </section>`
                    : ``}
            </div>
            ${args.split === "Vertically" || args.split === "Both"
                ? html`<div class="tlp-pane-container">
                      <section class="tlp-pane-section">
                          <p>${args.content}</p>
                      </section>
                  </div>`
                : ``}
        </section>`;
    },
    args: {
        title: "Pane",
        content: "The content of the pane goes here. The pane can have more than one section.",
        tabs: false,
        table: false,
        submit_button: false,
        split: "No splitting",
    },
    argTypes: {
        title: {
            name: "Title",
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
                type: { summary: null },
            },
        },
        tabs: {
            name: "With tabs",
            description: "Add an example of tabs",
            table: {
                type: { summary: null },
            },
        },
        table: {
            name: "With table",
            description: "Add an example of table",
            table: {
                type: { summary: null },
            },
        },
        submit_button: {
            name: "With submit button",
            description: "Add an example of submit button",
            table: {
                type: { summary: null },
            },
        },
    },
};
export default meta;
type Story = StoryObj<PaneProps>;

export const Pane: Story = {};

export const PaneWithTabs: Story = {
    args: {
        tabs: true,
    },
};

export const PaneWithTable: Story = {
    args: {
        table: true,
    },
};

export const PaneWithSubmitButton: Story = {
    args: {
        submit_button: true,
    },
};

export const HorizontallySplitPane: Story = {
    args: {
        split: "Horizontally",
    },
};

export const VerticallySplitPane: Story = {
    args: {
        split: "Vertically",
    },
};
