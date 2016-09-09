<?php
/**
 * Copyright (c) Enalean 2011. All rights reserved
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
    <h2><?= $GLOBALS['HTML']->getImage('homepage/user.png', array('alt' => "New user", 'width' => '48px')) ?> Participez</h2>
    <?php if ($current_user->isLoggedIn()) { ?>
        <p>Bienvenue <?= $current_user_display_name ?>. 
        <a href="/softwaremap/">Rejoignez un projet</a> ou créez en un nouveau.</p>
    <?php } else { ?>
        <p>Pour vous permettre d'utiliser toute la puissance de 
            <?= $GLOBALS['sys_name']?>, vous devez vous enregistrer 
            ne tant qu'utilisateur. C'est très simple et rapide et 
            ça vous permettra d'utiliser tout ce que nous avons à 
            vous offrir.
        </p>
        <form action="<?= $login_form_url ?>" method="POST">
            <?php
            $login_csrf = new CSRFSynchronizerToken('/account/login.php');
            echo $login_csrf->fetchHTMLInput();
            ?>
            <input type="text" name="form_loginname" class="<?= $login_input_span ?>" placeholder="Username" />
            <input type="password" name="form_pw" class="<?= $login_input_span ?>" placeholder="Password" />
            <input type="submit" class="btn" name="login" value="<?= $GLOBALS['Language']->getText('account_login', 'login_btn') ?>" />
            Ou <a href="/account/register.php">créez un compte</a>
        </form>
    <?php } 
?>