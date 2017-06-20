<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

//
// Purpose:
//    Default Web Page for groups that haven't setup their page yet
//   Please replace this file with your own website
if (preg_match('|^/www/(.*)/|',$_SERVER['REQUEST_URI'],$matches)) {
  $project_name = $matches[1];
  $default_domain = $_SERVER['HTTP_HOST'];
} else {
  $pieces = explode('.', $_SERVER['HTTP_HOST']);
  $project_name = array_shift($pieces);
  $default_domain = join('.',$pieces);
}

?>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title>欢迎</title>
        <link rel="stylesheet" href="http://<?php echo $default_domain; ?>/themes/common/css/style.css" type="text/css" />
        <link rel="stylesheet" href="http://<?php echo $default_domain; ?>/current_css.php" type="text/css" />
    </head>
    <body>
        <div class="container">
            <p>
                <a href="http://<?= $default_domain; ?>/"><b>本地的</b></a> |
                <a href="http://<?= $default_domain; ?>/contact.php"><b>联系我们</b></a> |
            </p>
            <div class="hero-unit">
                <img src="http://<?= $default_domain; ?>/themes/common/images/organization_logo.png" vspace="" hspace="7" border=0 alt="Organization logo">

                <h2>欢迎来到项目网页<?= $project_name ?></h2>
                <p>这个项目还没有建立自己的个人网站。请尽快检查更新或访问 <A href="http://<?= $default_domain; ?>/projects/<?= $project_name; ?>">项目总结</a></p>
            </div>
        </div>
    </body>
</html>
