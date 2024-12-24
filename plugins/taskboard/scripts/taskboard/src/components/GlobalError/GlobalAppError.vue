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
  -
  -->

<template>
    <section class="empty-state-page">
        <div class="empty-state-illustration">
            <global-app-error-illustration />
        </div>

        <h1 class="empty-state-title">
            {{ $gettext("Oops, there's an issue") }}
        </h1>
        <p class="empty-state-text">
            {{ $gettext("It seems an action you tried to perform can't be done.") }}
        </p>
        <template v-if="has_more_details">
            <p class="empty-state-text taskboard-error-link">
                <a v-if="!is_more_shown" v-on:click="is_more_shown = true" data-test="show-details">
                    {{ $gettext("Show error details") }}
                </a>
            </p>
            <pre v-if="is_more_shown" class="taskboard-error-details" data-test="details">{{
                global_error_message
            }}</pre>
        </template>
        <button type="button" class="tlp-button-primary empty-state-action" v-on:click="reloadPage">
            <i class="fas fa-sync tlp-button-icon" aria-hidden="true"></i>
            {{ $gettext("Reload the page") }}
        </button>
    </section>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace } from "vuex-class";

const error = namespace("error");
@Component({
    components: {
        "global-app-error-illustration": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "taskboard-global-app-error-illustration" */ "./GlobalAppErrorIllustration.vue"
            ),
    },
})
export default class GlobalAppError extends Vue {
    @error.State
    readonly global_error_message!: string;

    is_more_shown = false;

    reloadPage(): void {
        window.location.reload();
    }

    get has_more_details(): boolean {
        return this.global_error_message.length > 0;
    }
}
</script>
