<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div>
        <p>
            <input type="hidden" v-bind:name="'artifact[' + field_id + '][no_steps]'" value="1" />
            {{ $gettext("There isn't any step defined yet. Start by adding one.") }}
        </p>
        <div>
            <button
                type="button"
                class="btn btn-primary"
                v-on:click="addStep(0)"
                data-test="add-step"
            >
                <i class="fa-solid fa-plus"></i>
                {{ $gettext("Add step") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useMutations } from "vuex-composition-helpers";
import { strictInject } from "@tuleap/vue-strict-inject";
import { FIELD_ID } from "./injection-keys";

const field_id = strictInject(FIELD_ID);
const { addStep } = useMutations(["addStep"]);
</script>
