<!--
  - Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
    <section class="tlp-pane-section">
        <form
            v-bind:action="action_url"
            method="POST"
            class="tlp-form-element"
            v-bind:class="{ 'tlp-form-element-disabled': is_user_anonymous }"
            data-test="notifications-form"
        >
            <input type="hidden" v-bind:name="csrf_token.name" v-bind:value="csrf_token.value" />
            <input type="hidden" name="action" value="monitor" />
            <input type="hidden" name="id" v-bind:value="item_id" />
            <input type="hidden" name="monitor" value="0" />
            <div class="tlp-form-element">
                <label
                    class="tlp-label tlp-checkbox"
                    for="plugin_docman_monitor_item"
                    data-test="notify-me-checkbox"
                >
                    <input
                        type="checkbox"
                        name="monitor"
                        value="1"
                        id="plugin_docman_monitor_item"
                        data-test="notify-me-checkbox-input"
                        v-bind:checked="checked"
                        v-bind:disabled="is_user_anonymous"
                    />
                    {{ $gettext("Send me an email whenever this item is updated.") }}
                </label>
                <blockquote v-if="is_a_folder && !is_user_anonymous">
                    <input type="hidden" name="cascade" value="0" />
                    <label for="plugin_docman_monitor_cascade_item" class="tlp-label tlp-checkbox">
                        <input
                            type="checkbox"
                            name="cascade"
                            value="1"
                            id="plugin_docman_monitor_cascade_item"
                            data-test="notify-me-hierarchy-checkbox-input"
                            v-bind:checked="checked_cascade"
                        />
                        {{ $gettext("...and for the whole sub-hierarchy.") }}
                    </label>
                </blockquote>
            </div>
            <div class="tlp-pane-section-submit">
                <button
                    type="submit"
                    data-test="submit-notification-button"
                    class="tlp-button-primary"
                >
                    {{ $gettext("Submit") }}
                </button>
            </div>
        </form>
    </section>
</template>

<script setup lang="ts">
import { ref } from "vue";
import type { CsrfToken } from "../../type";

const props = defineProps<{
    is_user_notified: boolean;
    is_user_notified_for_cascade: boolean;
    is_a_folder: boolean;
    is_user_anonymous: boolean;
    item_id: number;
    action_url: string;
    csrf_token: CsrfToken;
}>();

const checked = ref(props.is_user_notified);
const checked_cascade = ref(props.is_user_notified_for_cascade);
</script>
