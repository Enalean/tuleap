<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
    <div
        class="tlp-form-element"
        v-bind:class="{ 'tlp-form-element-disabled': loading }"
        data-test="banner-active-form-element"
    >
        <label class="tlp-label tlp-checkbox">
            <input type="checkbox" v-model="banner_is_activated" data-test="banner-active" />
            {{ $gettext("Activate the banner on the whole platform") }}
        </label>
    </div>
    <div v-show="banner_is_activated">
        <div class="tlp-form-element siteadmin-platform-banner-importance">
            <label class="tlp-label">
                {{ $gettext("Importance") }}
                <i class="fas fa-asterisk" aria-hidden="true"></i>
            </label>
            <label class="tlp-label tlp-radio">
                <input
                    type="radio"
                    value="standard"
                    v-model="current_importance"
                    class="siteadmin-platform-banner-importance-standard"
                    data-test="banner-standard-importance"
                />
                <span class="tlp-text-info">{{ $gettext("Standard") }}</span>
            </label>
            <label class="tlp-label tlp-radio">
                <input
                    type="radio"
                    value="warning"
                    v-model="current_importance"
                    class="siteadmin-platform-banner-importance-warning"
                />
                <span class="tlp-text-warning">{{ $gettext("Warning") }}</span>
            </label>
            <label class="tlp-label tlp-radio">
                <input
                    type="radio"
                    value="critical"
                    v-model="current_importance"
                    class="siteadmin-platform-banner-importance-critical"
                />
                <span class="tlp-text-danger">{{ $gettext("Critical") }}</span>
            </label>
        </div>

        <expiration-date-banner-input
            v-bind:value="current_expiration_date"
            v-on:input="onExpirationDateChange"
        />

        <div
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': loading }"
            data-test="message-form-element"
        >
            <label class="tlp-label" for="description">
                {{ $gettext("Message") }}
                <i class="fas fa-asterisk" aria-hidden="true"></i>
            </label>
            <textarea
                ref="embedded_editor"
                class="tlp-textarea"
                id="description"
                required
                name="description"
                v-model="current_message"
                v-bind:placeholder="$gettext('Choose a banner message')"
                data-test="banner-message"
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
            data-test="save-button"
        >
            <i v-if="loading" class="tlp-button-icon fas fa-fw fa-spin fa-circle-notch"></i>
            <i v-if="!loading" class="tlp-button-icon fas fa-save"></i>
            {{ $gettext("Save the configuration") }}
        </button>
    </div>
</template>

<script setup lang="ts">
/* global CKEDITOR:readonly */

import { computed, onMounted, onUnmounted, ref } from "vue";
import { useGettext } from "vue3-gettext";
import type { BannerState, Importance } from "../type";
import "ckeditor4";
import ExpirationDateBannerInput from "./ExpirationDateBannerInput.vue";

const { $gettext } = useGettext();

const props = defineProps<{
    readonly message: string;
    readonly importance: Importance;
    readonly expiration_date: string;
    readonly loading: boolean;
}>();

const emit = defineEmits<{
    (e: "save-banner", state: BannerState): void;
}>();

const embedded_editor = ref<HTMLTextAreaElement>();
const banner_is_activated = ref(props.message !== "");
const current_message = ref(props.message);
const current_importance = ref<Importance>(props.importance);
const current_expiration_date = ref(props.expiration_date);

let editor: CKEDITOR.editor | null = null;

const should_tooltip_be_displayed = computed(
    (): boolean =>
        current_message.value.length === 0 && banner_is_activated.value && !props.loading,
);

const is_save_button_disabled = computed(
    (): boolean =>
        (current_message.value.length === 0 && banner_is_activated.value) || props.loading,
);

function onExpirationDateChange(value: string): void {
    current_expiration_date.value = value;
}

onMounted(() => {
    createEditor();
});

onUnmounted(() => {
    destroyEditor();
});

function createEditor(): void {
    destroyEditor();

    if (!(embedded_editor.value instanceof HTMLTextAreaElement)) {
        throw new Error("The ref embedded_editor is not a HTMLTextAreaElement");
    }
    editor = CKEDITOR.replace(embedded_editor.value, {
        toolbar: [
            ["Cut", "Copy", "Paste", "Undo", "Redo", "Link", "Unlink"],
            ["Bold", "Italic"],
        ],
        disableNativeSpellChecker: false,
        linkShowTargetTab: false,
    });

    editor.on("instanceReady", onInstanceReady);
}

function onInstanceReady(): void {
    if (editor === null) {
        return;
    }

    editor.on("change", onChange);

    editor.on("mode", () => {
        if (editor?.mode === "source") {
            const editable = editor.editable();
            editable.attachListener(editable, "input", () => {
                onChange();
            });
        }
    });
}

function onChange(): void {
    if (editor === null) {
        return;
    }
    current_message.value = editor.getData();
}

function destroyEditor(): void {
    editor?.destroy();
}

function save(): void {
    if (current_message.value.length === 0 && banner_is_activated.value) {
        return;
    }

    const banner_save_payload: BannerState = {
        message: current_message.value,
        importance: current_importance.value,
        expiration_date: current_expiration_date.value,
        activated: banner_is_activated.value,
    };

    emit("save-banner", banner_save_payload);
}
</script>
