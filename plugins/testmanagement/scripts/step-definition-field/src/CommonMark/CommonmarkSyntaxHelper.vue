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
            class="btn btn-small button-commonmark-syntax-helper"
            ref="button_helper"
            v-bind:disabled="is_in_preview_mode"
            data-test="button-helper"
        >
            <i class="fas fa-question-circle" aria-hidden="true"></i>
            <translate>Help</translate>
        </button>
        <section class="tlp-popover" id="popover-content" ref="popover_content">
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title" v-translate>For your information...</h1>
            </div>
            <table class="tlp-table">
                <thead>
                    <tr>
                        <th v-translate>Type...</th>
                        <th v-translate>...to get</th>
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
                            <pre><code>a = 'Hello';
b = 'World';
echo a.b;
#display Hello World
</code></pre>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

<script>
import $ from "jquery";

export default {
    name: "CommonmarkSyntaxHelper",
    props: {
        is_in_preview_mode: Boolean,
    },
    data() {
        return {
            escapeHandler: this.handleKeyUp.bind(this),
        };
    },
    mounted() {
        $(this.$refs.button_helper).popover({
            content: $(this.$refs.popover_content).html(),
            trigger: "click",
            html: true,
            placement: "right",
        });
        document.addEventListener("keyup", this.escapeHandler);
    },
    destroyed() {
        $(this.$refs.button_helper).popover("destroy");
        document.removeEventListener("keyup", this.escapeHandler);
    },
    methods: {
        handleKeyUp(event) {
            if (event.key !== "Escape") {
                return;
            }
            $(this.$refs.button_helper).popover("hide");
        },
    },
};
</script>
