<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
    <div>
        <button
            type="button"
            class="tlp-button-secondary tlp-button-small artifact-modal-helper-popover-button"
            data-placement="left"
            data-trigger="click"
            ref="button_helper"
            v-bind:disabled="disabled"
            data-test="artifact-modal-helper-popover-button"
        >
            <i class="fas fa-question-circle tlp-button-icon" aria-hidden="true"></i>
            {{ help }}
        </button>

        <section class="tlp-popover" ref="popover_helper">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">{{ for_your_information }}</h1>
            </div>
            <div class="tlp-popover-body">
                <table class="tlp-table">
                    <thead>
                        <tr>
                            <th>
                                {{ type }}
                            </th>
                            <th>
                                {{ to_get }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>_italic_</td>
                            <td><em>italic</em></td>
                        </tr>
                        <tr>
                            <td>**bold**</td>
                            <td><b>bold</b></td>
                        </tr>
                        <tr>
                            <td># Heading 1</td>
                            <td><h1 class="popover-h1-indentation">Heading 1</h1></td>
                        </tr>
                        <tr>
                            <td>## Heading 2</td>
                            <td><h2 class="popover-h2-indentation">Heading 2</h2></td>
                        </tr>
                        <tr>
                            <td>[Link](https://example.com)</td>
                            <td><a href="https://example.com">Link</a></td>
                        </tr>
                        <tr>
                            <td>![Image](/path/image.png)</td>
                            <td>
                                <img
                                    class="popover-image-indentation"
                                    src="../assets/image_example_commonmark.png"
                                />
                            </td>
                        </tr>
                        <tr>
                            <td>> Blockquote</td>
                            <td>
                                <blockquote class="popover-blockquote-indentation">
                                    Blockquote
                                </blockquote>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                - Item 1
                                <br />
                                - Item 2
                                <br />
                            </td>
                            <td>
                                <ul>
                                    <li>Item 1</li>
                                    <li>Item 2</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                1. Item 1
                                <br />
                                2. Item 2
                                <br />
                            </td>
                            <td>
                                <ol>
                                    <li>Item 1</li>
                                    <li>Item 2</li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>`Inline code`</td>
                            <td><code>Inline code</code></td>
                        </tr>
                        <tr>
                            <td>
                                ```
                                <br />
                                a = 'Hello ';
                                <br />
                                b = 'World';
                                <br />
                                echo a.b;
                                <br />
                                # display Hello World
                                <br />
                                ```
                            </td>
                            <td>
                                <pre class="popover-code-block-indentation">
                                    <code>
                                        a = 'Hello';
                                        b = 'World';
                                        echo a.b;
                                        #display Hello World
                                    </code>
                                </pre>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script>
import { createPopover } from "tlp";
import {
    getCommonMarkSyntaxHelperPopoverTitle,
    getSyntaxHelperTitle,
    getSyntaxHelperToGet,
    getSyntaxHelperType,
} from "../gettext-catalog";

export default {
    name: "CommonmarkSyntaxHelper",
    props: {
        disabled: Boolean,
    },
    data() {
        return {
            escapeHandler: this.handleKeyUp.bind(this),
            popover: undefined,
        };
    },
    computed: {
        // Translate attribute does not work with ng-vue out of the box.
        help() {
            return getSyntaxHelperTitle();
        },
        type() {
            return getSyntaxHelperType();
        },
        to_get() {
            return getSyntaxHelperToGet();
        },
        for_your_information() {
            return getCommonMarkSyntaxHelperPopoverTitle();
        },
    },
    mounted() {
        this.popover = createPopover(this.$refs.button_helper, this.$refs.popover_helper);
        document.addEventListener("keyup", this.escapeHandler);
    },
    destroyed() {
        document.removeEventListener("keyup", this.escapeHandler);
        if (this.popover) {
            this.popover.destroy();
        }
    },
    methods: {
        handleKeyUp(event) {
            if (event.key !== "Escape") {
                return;
            }
            if (this.popover) {
                this.popover.hide();
            }
        },
    },
};
</script>
