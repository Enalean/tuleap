<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -->

<template>
    <div class="tlp-form-element" v-show="is_displayed">
        <label class="tlp-label" for="document-new-item-embedded">{{ $gettext("Content") }}</label>
        <div class="tlp-form-element">
            <textarea
                class="tlp-textarea"
                id="document-new-item-embedded"
                name="embedded-content"
                ref="embedded_editor"
                v-bind:placeholder="placeholder"
                v-bind:value="value.content"
            ></textarea>
        </div>
    </div>
</template>

<script>
import { isEmbedded } from "../../../../helpers/type-check-helper";

export default {
    name: "EmbeddedProperties",
    props: {
        value: Object,
        item: Object,
    },
    data() {
        return {
            editor: null,
        };
    },
    computed: {
        is_displayed() {
            return isEmbedded(this.item);
        },
        placeholder() {
            return this.$gettext("My content...");
        },
    },
    mounted() {
        const text_area = this.$refs.embedded_editor;
        if (this.editor !== null) {
            this.editor.destroy();
        }

        // eslint-disable-next-line no-undef
        this.editor = CKEDITOR.replace(text_area, {
            toolbar: [
                [
                    "Cut",
                    "Copy",
                    "Paste",
                    "PasteText",
                    "PasteFromWord",
                    "-",
                    "Undo",
                    "Redo",
                    "Link",
                    "Unlink",
                    "Anchor",
                ],
                ["Image", "Table", "HorizontalRule", "SpecialChar", "-", "Source"],
                "/",
                ["Bold", "Italic", "Strike", "-"],
                ["RemoveFormat", "NumberedList", "BulletedList", "Styles", "Format"],
            ],
            stylesSet: [
                { name: "Bold", element: "strong", overrides: "b" },
                { name: "Italic", element: "em", overrides: "i" },
                { name: "Strike", element: "s" },
                { name: "Code", element: "code" },
                { name: "Subscript", element: "sub" },
                { name: "Superscript", element: "sup" },
            ],
            disableNativeSpellChecker: false,
            linkShowTargetTab: false,
        });

        this.editor.on("instanceReady", this.onInstanceReady);
    },
    methods: {
        onChange() {
            const new_content = this.editor.getData();
            this.$emit("input", { content: new_content });
        },
        onInstanceReady() {
            this.editor.on("change", this.onChange);

            this.editor.on("mode", () => {
                if (this.editor.mode === "source") {
                    const editable = this.editor.editable();
                    editable.attachListener(editable, "input", () => {
                        this.onChange();
                    });
                }
            });
        },
        beforeUnmount() {
            if (this.editor) {
                this.editor.destroy();
            }
        },
    },
};
</script>
