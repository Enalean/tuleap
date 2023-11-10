<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div>
        <div class="tlp-form-element" v-bind:class="{ 'tlp-form-element-disabled': loading }">
            <label class="tlp-label tlp-checkbox"
                ><input type="checkbox" v-model="banner_is_activated" />{{
                    $gettext("Activate the banner on this project")
                }}</label
            >
        </div>
        <div v-show="banner_is_activated">
            <div class="tlp-form-element" v-bind:class="{ 'tlp-form-element-disabled': loading }">
                <label class="tlp-label" for="description">{{ $gettext("Message") }}</label>
                <textarea
                    ref="embedded_editor"
                    class="tlp-textarea"
                    id="description"
                    name="description"
                    data-test="banner-message"
                    v-model="current_message"
                    v-bind:placeholder="$gettext('Choose a banner message')"
                ></textarea>
                <p class="tlp-text-muted">
                    {{ $gettext("Your message will be condensed to one line") }}
                </p>
            </div>
        </div>
        <div class="tlp-pane-section-submit">
            <button
                type="button"
                class="tlp-button-primary"
                v-bind:data-tlp-tooltip="$gettext('Message is mandatory')"
                v-bind:class="{ 'tlp-tooltip tlp-tooltip-top': should_tooltip_be_displayed }"
                v-on:click="save"
                v-bind:disabled="is_save_button_disabled"
            >
                <i
                    v-if="loading"
                    class="tlp-button-icon fa fa-fw fa-spin fa-circle fa-circle-o-notch"
                ></i>
                <i v-if="!loading" class="tlp-button-icon fa fa-save"></i
                >{{ $gettext("Save the configuration") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import CKEDITOR from "ckeditor4";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import type { BannerState } from "../type";

const props = defineProps<{
    message: string;
    loading: boolean;
}>();

const emit = defineEmits<{
    (e: "save-banner", payload: BannerState): void;
}>();

const banner_is_activated = ref(props.message !== "");
const current_message = ref(props.message);
const embedded_editor = ref<HTMLTextAreaElement | null>(null);
let editor: CKEDITOR.editor | null = null;

const should_tooltip_be_displayed = computed(
    () => current_message.value.length === 0 && banner_is_activated.value && !props.loading,
);

const is_save_button_disabled = computed(
    () =>
        (current_message.value.length === 0 && banner_is_activated.value) || props.loading === true,
);

const onChange = (): void => {
    if (editor === null) {
        return;
    }

    current_message.value = editor.getData();
};

const onInstanceReady = (): void => {
    if (editor === null) {
        return;
    }

    editor.on("change", onChange);

    editor.on("mode", () => {
        if (editor === null) {
            return;
        }

        if (editor.mode === "source") {
            const editable = editor.editable();
            editable.attachListener(editable, "input", () => {
                onChange();
            });
        }
    });
};

const destroyEditor = (): void => {
    if (editor !== null) {
        editor.destroy();
        editor = null;
    }
};

const createEditor = (): void => {
    destroyEditor();

    const text_area = embedded_editor.value;
    if (!(text_area instanceof HTMLTextAreaElement)) {
        throw new Error("The ref embedded_editor is not a HTMLTextAreaElement");
    }

    editor = CKEDITOR.replace(text_area, {
        toolbar: [
            ["Cut", "Copy", "Paste", "Undo", "Redo", "Link", "Unlink"],
            ["Bold", "Italic"],
        ],
        disableNativeSpellChecker: false,
        linkShowTargetTab: false,
    });

    editor.on("instanceReady", onInstanceReady);
};

const save = (): void => {
    if (current_message.value.length === 0 && banner_is_activated.value) {
        return;
    }

    const banner_save_payload: BannerState = {
        message: current_message.value,
        activated: banner_is_activated.value,
    };

    emit("save-banner", banner_save_payload);
};

onMounted(createEditor);
onBeforeUnmount(destroyEditor);
</script>
