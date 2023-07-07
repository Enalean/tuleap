<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
  -->

<template>
    <section
        class="empty-state-page document-folder-with-error"
        data-test="document-user-can-not-read-document"
    >
        <div class="empty-state-illustration">
            <item-permission-error-svg />
        </div>
        <h1 class="empty-state-title">
            {{ $gettext("You don't have read permission for this item") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("You may only access documents you are granted read permission on.") }}
        </p>
        <form
            v-bind:action="`/plugins/document/PermissionDeniedRequestMessage/${project_id}`"
            method="post"
            name="display_form"
            ref="form_anchor"
            class="tlp-pane document-request-permission-pane"
        >
            <div class="tlp-pane-container">
                <section class="tlp-pane-section">
                    <input type="hidden" v-bind:name="csrf_token_name" v-bind:value="csrf_token" />
                    <div class="tlp-form-element">
                        <label class="tlp-label" for="msg_private_project">
                            {{
                                $gettext(
                                    "Write your message below and click on the button to send your request to the project administrators"
                                )
                            }}
                            <i class="fa-solid fa-asterisk"></i>
                        </label>
                        <textarea
                            class="tlp-textarea"
                            rows="5"
                            id="msg_private_project"
                            data-test="message-request-access-private-document"
                            name="msg_private_project"
                            v-bind:placeholder="placeholder"
                            v-model="mail_content"
                            required
                        ></textarea>
                        <input type="hidden" name="groupId" v-bind:value="project_id" />
                        <p v-if="error !== ''" class="tlp-text-danger">
                            {{
                                $gettext(
                                    "Please enter a reason for why you need to access this document."
                                )
                            }}
                        </p>
                    </div>
                </section>
                <section class="tlp-pane-section tlp-pane-section-submit">
                    <button
                        type="button"
                        class="tlp-button-primary"
                        v-on:click="submit"
                        data-test="private-document-access-button"
                    >
                        <i class="fa-regular fa-envelope tlp-button-icon"></i>
                        {{ $gettext("Send mail") }}
                    </button>
                </section>
            </div>
        </form>
    </section>
</template>
<script setup lang="ts">
import ItemPermissionErrorSvg from "../../svg/error/ItemPermissionErrorSvg.vue";
import { computed, ref } from "vue";
import { useGettext } from "vue3-gettext";
import { useNamespacedState } from "vuex-composition-helpers";
import type { ConfigurationState } from "../../../store/configuration";

const { $gettext } = useGettext();

defineProps<{
    csrf_token: string;
    csrf_token_name: string;
}>();

let error = ref("");
let mail_content = ref("");

const { project_id } = useNamespacedState<Pick<ConfigurationState, "project_id">>("configuration", [
    "project_id",
]);

const form_anchor = ref<InstanceType<typeof HTMLFormElement>>();

function submit(): void {
    if (!mail_content.value) {
        error.value = $gettext("Mail content is required");
        return;
    }
    if (form_anchor.value) {
        form_anchor.value.submit();
    }
}
const placeholder = computed((): string => {
    return $gettext("Please write something meaningful for the admin.");
});
</script>
