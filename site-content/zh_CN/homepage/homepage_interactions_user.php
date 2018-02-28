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
    <h2><?= $GLOBALS['HTML']->getImage('homepage/user.png', array('alt' => "New user", 'width' => '48px')) ?> Participate</h2>
    <?php if ($current_user->isLoggedIn()) { ?>
        <p>欢迎 <?= $current_user_display_name ?>. 
        你现在可以充分利用<?= $GLOBALS['sys_name']?>. 
        <a href="/softwaremap/">加入项目</a> 或者在下面创建一个新的。</p>
    <?php } else { ?>
        <p>为了充分利用 <?= $GLOBALS['sys_name']?>, 你应该注册为网站用户。它很容易和快速允许您充分参与我们提供的所有内容。
        </p>
        <form action="<?= $login_form_url ?>" method="POST">
            <?php
            $login_csrf = new CSRFSynchronizerToken('/account/login.php');
            echo $login_csrf->fetchHTMLInput();
            ?>
            <input type="text" name="form_loginname" class="<?= $login_input_span ?>" placeholder="Username" />
            <input type="password" name="form_pw" class="<?= $login_input_span ?>" placeholder="Password" />
            <input type="submit" class="btn" name="login" value="<?= $GLOBALS['Language']->getText('account_login', 'login_btn') ?>" />
            Or <a href="/account/register.php">创建一个帐户</a>
        </form>
    <?php } 
?>