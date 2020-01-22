<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
<h3 class="footer-section-title">Tuleap</h3>
<p class="footer-paragraph">
    Tuleap, outil de développement logiciel agile 100% open source. <a href="https://www.tuleap.org?utm_source=forge&utm_medium=forge&utm_campaign=forge">www.tuleap.org</a>.
</p>
<p class="footer-paragraph">
    <a href="https://www.tuleap.org/?utm_source=forge&utm_medium=forge&utm_campaign=forge">
        <?php echo $version ?>
    </a>
    <?php if ($GLOBALS['Language']->hasText('global', 'copyright')) { ?>
        <br><?php echo $GLOBALS['Language']->getOverridableText('global', 'copyright'); ?>.
    <?php } ?>
</p>
