<?php
/**
 * Copyright (c) Enalean 2013. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
?>
    <h3>Comment utiliser un dépôt Git:</h3>
    <dl>
        <dt>Clonez le dépôt pour obtenir votre copie de travail locale:</dt>
        <dd>
            <pre>
git clone <span class="plugin_git_example_url"><?= $url ?></span> <?= $name ?> 
cd <?= $name ?>
            </pre>
        </dd>
        <dt>Ou ajoutez simplement ce dépôt distant à votre copie locale:</dt>
        <dd>
            <pre>
git remote add <?= $name ?> <span class="plugin_git_example_url"><?= $url ?></span>
git fetch <?= $name ?> 
git checkout -b my-local-tracking-branch <?= $name ?>/master
            </pre>
        </dd>
    </dl>
