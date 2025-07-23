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
    <div class="tlp-form-element">
        <label class="tlp-label" for="document-new-item-embedded">{{ $gettext("Content") }}</label>
        <div class="tlp-form-element">
            <textarea
                class="tlp-textarea"
                id="document-new-item-embedded"
                name="embedded-content"
                ref="embedded_editor"
                v-bind:placeholder="$gettext('My content...')"
                v-bind:value="value"
            ></textarea>
        </div>
    </div>
</template>

<script setup lang="ts">
import emitter from "../../../../helpers/emitter";
import { onBeforeUnmount, onMounted, ref } from "vue";

defineProps<{ value: string }>();

const embedded_editor = ref<HTMLTextAreaElement>();
let editor = null;

onMounted(() => {
    const text_area = embedded_editor.value;
    if (editor !== null) {
        editor.destroy();
    }

    // eslint-disable-next-line no-undef
    editor = CKEDITOR.replace(text_area, {
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

    editor.on("instanceReady", onInstanceReady);
});

onBeforeUnmount(() => {
    if (editor) {
        editor.destroy();
    }
});

function onChange(): void {
    if (editor.getData()) {
        emitter.emit("update-embedded-properties", editor.getData());
    }
}

function onInstanceReady() {
    editor.on("change", onChange);

    editor.on("mode", () => {
        if (editor.mode === "source") {
            const editable = editor.editable();
            editable.attachListener(editable, "input", () => {
                onChange();
            });
        }
    });
}
</script>
