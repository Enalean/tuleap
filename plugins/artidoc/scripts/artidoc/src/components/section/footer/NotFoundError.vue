<!--
  - Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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
    <div class="tlp-alert-danger">
        <p>
            {{
                $gettext(
                    "The section you are trying to update does not exist or you don't have anymore access to it.",
                )
            }}
            <br />
            {{
                $gettext(
                    "You should reload the page to have a clean state (you will loose your modifications though).",
                )
            }}
        </p>

        <div class="alert-error-buttons">
            <button type="button" class="tlp-button-danger" v-on:click="reload">
                <i class="fa-solid fa-rotate-right tlp-button-icon" aria-hidden="true"></i>
                {{ $gettext("Reload page") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";

const { $gettext } = useGettext();

function reload(): void {
    window.location.reload();
}
</script>

<style scoped lang="scss">
.alert-error-buttons {
    margin: var(--tlp-small-spacing) 0 0;
}
</style>
