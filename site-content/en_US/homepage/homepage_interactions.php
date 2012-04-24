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
<div class="row-fluid">
    <h2><?= $GLOBALS['HTML']->getImage('homepage/user.png', array('alt' => "New user", 'width' => '48px')) ?> Participate</h2>
    <?php if ($current_user->isLoggedIn()) { ?>
        <p>Welcome <?= $current_user_display_name ?>. 
        You can now get the most out of <?= $GLOBALS['sys_name']?>. 
        <a href="/softwaremap/">Join a project</a> or create a new one below.</p>
    <?php } else { ?>
        <p>In order to get the most out of <?= $GLOBALS['sys_name']?>, you should 
        register as a site user. It's easy and fast and it 
        allows you to participate fully in all we have to offer.
        </p>
        <form action="<?= $login_form_url ?>" method="POST">
            <input type="text" name="form_loginname" class="<?= $login_input_span ?>" placeholder="Username" />
            <input type="password" name="form_pw" class="<?= $login_input_span ?>" placeholder="Password" />
            <input type="submit" class="btn" name="login" value="<?= $GLOBALS['Language']->getText('account_login', 'login_btn') ?>" />
            Or <a href="/account/register.php">create an account</a>
        </form>
    <?php } ?>
</div>
<hr />
<div class="row-fluid">
    <h2><?= $GLOBALS['HTML']->getImage('homepage/join.png', array('alt' => "Join a project", 'width' => '48px')) ?> Create a new project</h2>
    <?php 
        $create_your_own_project = 'create your own project';
        if ($current_user->isLoggedIn()) {
            $create_your_own_project = '<a href="/project/register.php">'. $create_your_own_project .'</a>';
        }
    ?>
    <p>It's very easy to <?= $create_your_own_project ?>. Login, 
    leverage project templates and customize your 
    workspace in the administration interface.</p>
</div>
<?php
?>