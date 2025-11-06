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
    <div
        class="tlp-card tlp-skeleton-card commit-card-skeleton"
        v-for="(item, index) in Array(5)"
        v-bind:key="index"
    >
        <div class="commit-card-content-left">
            <div class="tlp-avatar"></div>
            <div>
                <div>
                    <h3 class="commit-card-title">
                        <span class="tlp-skeleton-text" style="width: 400px"></span>
                    </h3>
                </div>
                <div><span class="tlp-skeleton-text" style="width: 200px"></span></div>
            </div>
        </div>
        <span class="tlp-skeleton-text badge-skeleton" style="width: 100px"></span>
    </div>
</template>

<style scoped lang="scss">
.commit-card-skeleton {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--tlp-small-spacing);
}

.commit-card-content-left {
    display: flex;
    flex: 1 1 auto;
    gap: var(--tlp-small-spacing);
}

.commit-card-title {
    margin: 0 0 var(--tlp-small-spacing) 0;
}
</style>
