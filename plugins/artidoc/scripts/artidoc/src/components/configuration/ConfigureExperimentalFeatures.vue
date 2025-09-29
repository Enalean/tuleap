<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
    <div class="tlp-modal-body">
        <div class="tlp-form-element">
            <label class="tlp-label" for="activate-display-of-versions">
                {{ $gettext("Fake display of versions") }}
            </label>
            <div class="tlp-switch">
                <input
                    type="checkbox"
                    id="activate-display-of-versions"
                    class="tlp-switch-checkbox"
                    v-bind:checked="are_versions_displayed"
                    v-on:change="toggleVersionsDisplayed"
                    data-test="switch"
                />
                <label for="activate-display-of-versions" class="tlp-switch-button"></label>
            </div>
        </div>

        <p>
            {{ $gettext("This will display a fake list of versions next to Artidoc.") }}
        </p>
        <p>
            {{
                $gettext(
                    "This option is here to gather feedback about versions display, feel free to get back to Tuleap development team if you have something to share.",
                )
            }}
        </p>
        <p>
            <strong>{{ $gettext("This activation is personal and not persistent.") }}</strong>
            {{
                $gettext("As soon as you reload the page, the feature will no longer be displayed.")
            }}
        </p>
        <p>
            {{
                $gettext(
                    "Please note that you can display directly the versions with the following shortcut:",
                )
            }}
            <kbd>v</kbd>
        </p>
    </div>
    <div class="tlp-modal-footer">
        <button
            type="button"
            class="tlp-button-primary tlp-button-outline tlp-modal-action"
            v-on:click="closeModal(false)"
        >
            {{ $gettext("Close") }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import { strictInject } from "@tuleap/vue-strict-inject";
import { ARE_VERSIONS_DISPLAYED } from "@/can-user-display-versions-injection-key";
import { CLOSE_CONFIGURATION_MODAL } from "@/components/configuration/configuration-modal";

const closeModal = strictInject(CLOSE_CONFIGURATION_MODAL);

const { $gettext } = useGettext();

const are_versions_displayed = strictInject(ARE_VERSIONS_DISPLAYED);

function toggleVersionsDisplayed(): void {
    are_versions_displayed.value = !are_versions_displayed.value;
}
</script>
