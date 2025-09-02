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
import "./ModalWrapper.js";
import "./modal.scss";
import { USER_INTERFACE_COLORS, type UserInterfaceColorName } from "@tuleap/core-constants";

type ModalProps = {
    title: string;
    subtitle: string;
    content: string;
    user_interface_color: "primary" | UserInterfaceColorName;
    size: "default" | "medium-sized" | "full-screen";
    buttonText: string;
    keyboard: boolean;
    destroy_on_hide: boolean;
    dismiss_on_backdrop_click: boolean;
    story:
        | "simple"
        | "with sections"
        | "big buttons"
        | "big content"
        | "data-modal-focus attribute"
        | "events";
};

function getWrapperClass(args: ModalProps): string {
    const classes = ["modal-wrapper"];
    if (args.story === "big content") {
        classes.push("modal-big-wrapper");
    }
    if (
        args.story === "data-modal-focus attribute" ||
        args.story === "with sections" ||
        args.story === "events"
    ) {
        classes.push("modal-medium-wrapper");
    }
    return classes.join(" ");
}

function getModalClasses(args: ModalProps): string {
    const classes = ["tlp-modal"];
    if (args.size !== "default") {
        classes.push(`tlp-modal-${args.size}`);
    }
    if (args.user_interface_color !== "primary") {
        classes.push(`tlp-modal-${args.user_interface_color}`);
    }
    return classes.join(" ");
}

function getBodyClasses(args: ModalProps): string {
    const classes = ["tlp-modal-body"];
    if (args.story === "with sections") {
        classes.push(`tlp-modal-body-with-sections`);
    }
    return classes.join(" ");
}

function getFooterClasses(args: ModalProps): string {
    const classes = ["tlp-modal-footer"];
    if (args.story === "big buttons") {
        classes.push("tlp-modal-footer-large");
    }
    return classes.join(" ");
}

function getTemplate(args: ModalProps): TemplateResult {
    // prettier-ignore
    return html`
<button id="modal-button" type="button" data-target-modal-id="modal" class="tlp-button-${args.user_interface_color}">
    Open modal
</button>

<div id="modal" class=${getModalClasses(args)} role="dialog" aria-labelledby="modal-label">
    <div class="tlp-modal-header">
        <h1 class="tlp-modal-title" id="modal-label">${args.title}</h1>
        <button class="tlp-modal-close" type="button" data-dismiss="modal" aria-label="Close">
            <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
        </button>
    </div>${args.story === "with sections" ? html`
    <div class="tlp-modal-feedback">
        <div class="tlp-alert-danger">
            Warning! Better check yourself, you're not looking too good.
        </div>
    </div>` : ''}
    <div class=${getBodyClasses(args)}> ${args.story === "with sections" ? html`
        <div class="tlp-modal-body-section">
            <h2 class="tlp-modal-subtitle">${args.subtitle}</h2>
            <p>${args.content}</p>
        </div>
        <div class="tlp-modal-body-section">
            <h2 class="tlp-modal-subtitle">Other section subtitle</h2>
            <p>Another body</p>
        </div>` : args.story === "big content" ? html`
        <p>
          Lorem ipsum dolor sit amet, consectetur adipiscing elit. Cras
          imperdiet semper congue. Praesent vulputate tortor elementum,
          condimentum enim at, pharetra tortor. Curabitur vitae laoreet
          nibh, sed fermentum erat. Sed non consectetur augue, nec efficitur
          dui. Mauris eleifend non turpis sed pretium. Aenean facilisis nec
          mauris vitae feugiat. Fusce eu tortor sit amet nulla sagittis
          ornare vel vitae nisi. Fusce nisl lacus, varius dapibus augue eu,
          elementum aliquet est. Nam euismod tristique diam pretium auctor.
          Quisque scelerisque ornare tellus, et faucibus risus pulvinar vel.
          Fusce pellentesque fermentum sapien id bibendum. Nam nibh turpis,
          gravida et ipsum quis, tempus posuere nisl. Phasellus id pretium
          felis, ac auctor purus.
      </p>
      <ul>
          <li>Pellentesque mollis risus a dignissim interdum.</li>
          <li>Nunc gravida nisl vitae felis feugiat congue.</li>
          <li>
              Sed accumsan purus eu nibh cursus, eu viverra nisi finibus.
          </li>
          <li>Morbi ut ante sit amet risus vehicula faucibus.</li>
      </ul>
      <p class="tlp-text-success">
          <i class="fa-solid fa-shuttle-space" aria-hidden="true"></i>
          Integer et ullamcorper mauris. Duis.
      </p>
      <p>
          Proin vitae vulputate metus. Nullam interdum vehicula enim, quis
          luctus nisi sodales a. Pellentesque id sem ut orci ultricies
          eleifend. In nec posuere ipsum. Sed eget nunc sed magna euismod
          laoreet. Vestibulum congue nisi augue, in varius turpis elementum
          et. Cras eu rhoncus lectus, at tincidunt ante.
      </p>
      <p>
          Fusce massa arcu, congue ut mauris vel, dignissim euismod orci.
          Nunc imperdiet pharetra dui, quis consequat nisl imperdiet et.
          Aenean eget varius mi, et consequat metus. Vestibulum ante ipsum
          primis in faucibus orci luctus et ultrices posuere cubilia Curae;
          Etiam porta diam a elit finibus vehicula. Praesent ut ultricies
          quam. Pellentesque ut ipsum id tellus venenatis sagittis ut eu
          velit. Morbi non sapien finibus, vehicula massa sed, rutrum ipsum.
          Nam vestibulum augue sit amet mauris volutpat rhoncus. Aliquam eu
          dapibus massa. Cras elementum massa massa.
      </p>` : args.story === "data-modal-focus attribute" ? html`
      <div class="tlp-form-element">
        <label class="tlp-label" for="first-input-element">First input element</label>
        <input id="first-input-element" class="tlp-input" type="text" placeholder="First input element">
      </div>

      <div class="tlp-form-element">
        <label class="tlp-label" for="data-modal-focus-element">Element with a data-modal-focus attribute</label>
        <input id="data-modal-focus-element" data-modal-focus class="tlp-input" type="text" placeholder="Element with a data-modal-focus attribute">
      </div>` : args.story === "events" ? html`
            <div class="tlp-alert-info">
                This modal will ask you to confirm before closing it, so that you do not lose ongoing work.
            </div>
        <div class="tlp-form-element">
            <label for="events-modal-input" class="tlp-label">Title</label>
            <input type="text" id="events-modal-input" class="tlp-input">
        </div>
        <div class="tlp-form-element">
            <label for="events-modal-text" class="tlp-label">Description</label>
            <textarea name="" id="events-modal-text" class="tlp-textarea" rows="2"></textarea>
        </div>
    ` : html`
      <h2 class="tlp-modal-subtitle">${args.subtitle}</h2>
      <p>${args.content}</p>`}
    </div>
    <div class=${getFooterClasses(args)}> ${args.story === "big buttons" ? html`
      <button type="button" class="tlp-button-${args.user_interface_color} tlp-button-large tlp-button-outline tlp-modal-action" data-dismiss="modal">
          Cancel
      </button>
      <button type="button" class="tlp-button-${args.user_interface_color} tlp-button-large tlp-modal-action">Action</button>
    ` : html`
      <button id="button-close" type="button" class="tlp-button-${args.user_interface_color} tlp-button-outline tlp-modal-action" data-dismiss="modal">
          Cancel
      </button>
      <button type="button" class="tlp-button-${args.user_interface_color} tlp-modal-action">Action</button>`}
    </div>
</div>`
}

const meta: Meta<ModalProps> = {
    title: "TLP/Fly Over/Modals",
    component: "tlp-modal",
    parameters: {
        controls: {
            exclude: ["story"],
        },
    },
    render: (args) => {
        return getTemplate(args);
    },
    args: {
        title: "Modal title",
        subtitle: "Subtitle",
        content: "One fine body…",
        user_interface_color: "primary",
        size: "default",
        story: "simple",
    },
    argTypes: {
        title: {
            name: "Title",
        },
        subtitle: {
            name: "Subtitle",
        },
        content: {
            name: "Content",
        },
        size: {
            name: "Size",
            description: "Add the class",
            control: "select",
            options: ["default", "medium-sized", "full-screen"],
            table: {
                type: { summary: ".tlp-modal-medium-sized or .tlp-modal-full-screen" },
            },
        },
        user_interface_color: {
            name: "UI color",
            description: "UI color of the modal",
            control: "select",
            options: [...USER_INTERFACE_COLORS, "primary"],
            table: {
                type: { summary: undefined },
            },
        },
    },
    decorators: [
        (story, { args }: { args: ModalProps }): TemplateResult =>
            html`<div class=${getWrapperClass(args)}>
                <tuleap-modal-wrapper
                    story=${args.story}
                    .keyboard=${args.keyboard}
                    .dismiss_on_backdrop_click=${args.dismiss_on_backdrop_click}
                    >${story()}</tuleap-modal-wrapper
                >
            </div>`,
    ],
};

export default meta;
type Story = StoryObj<ModalProps>;

export const Modal: Story = {
    args: {
        keyboard: true,
        destroy_on_hide: false,
        dismiss_on_backdrop_click: true,
    },
    argTypes: {
        keyboard: {
            description: "When true, enables closing the modal when pressing the Escape key",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: "true" },
            },
        },
        destroy_on_hide: {
            control: false,
            description:
                "When true destroys the event listeners when hiding the modal. Useful when for example you create new modals on a button click",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: "false" },
            },
        },
        dismiss_on_backdrop_click: {
            description: "When true, dismiss the modal when user clicks on the backdrop",
            table: {
                type: { summary: "boolean" },
                defaultValue: { summary: "true" },
            },
        },
    },
};

export const WithSections: Story = {
    args: {
        story: "with sections",
    },
};

export const WithBigActionButtons: Story = {
    args: {
        story: "big buttons",
    },
};

export const WithBigContent: Story = {
    args: {
        story: "big content",
    },
    argTypes: {
        content: {
            control: false,
        },
    },
};

export const WithDataModalFocusAttribute: Story = {
    args: {
        story: "data-modal-focus attribute",
    },
    argTypes: {
        content: {
            control: false,
        },
    },
};

export const WithEvents: Story = {
    args: {
        story: "events",
    },
    argTypes: {
        content: {
            control: false,
        },
    },
};
