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
    <h2><?= $GLOBALS['HTML']-> getImage('homepage/join.png', array('alt' => "Join a project", 'width' => '48px')) ?> 创建一个新的项目</h2>
    <?php 
        $create_your_own_project = 'create your own project';
        if ($current_user->isLoggedIn()) {
            $create_your_own_project = '<a href="/project/register.php">'. $create_your_own_project .'</a>';
        }
    ?>
    <p>这是很容易的 <?= $create_your_own_project ?>. 登录，
利用项目模板和自定义您的管理界面中的工作区。
</p>
<?php
?>