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
            <translate>There isn't any step defined yet. Start by adding one.</translate>
        </p>
        <div>
            <button type="button" class="btn btn-primary" v-on:click="addStep(0)">
                <i class="fa fa-plus"></i>
                <translate>Add step</translate>
            </button>
        </div>
    </div>
</template>

<script>
import { mapState, mapMutations } from "vuex";
export default {
    name: "StepDefinitionNoStep",
    computed: {
        ...mapState(["field_id"]),
    },
    methods: {
        ...mapMutations(["addStep"]),
    },
};
</script>
