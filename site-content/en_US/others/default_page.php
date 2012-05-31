<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
        <title>Welcome</title>
        <link rel="stylesheet" href="http://<?php echo $default_domain; ?>/themes/common/css/style.css" type="text/css" />
        <link rel="stylesheet" href="http://<?php echo $default_domain; ?>/current_css.php" type="text/css" />
    </head>
    <body>
        <div class="container">
            <p>
                <a href="http://<?= $default_domain; ?>/"><b>Home</b></a> |
                <a href="http://<?= $default_domain; ?>/contact.php"><b>Contact Us</b></a> |
                <a href="http://<?= $default_domain; ?>/account/logout.php"><b>Logout</b></a>
            </p>
            <div class="hero-unit">
                <img src="http://<?= $default_domain; ?>/themes/common/images/organization_logo.png" vspace="" hspace="7" border=0 alt="Organization logo">

                <h2>Welcome to the web page of project <?= $project_name ?></h2>
                <p>This project hasn't yet set up its personal web site. Please check back soon for updates or visit the <A href="http://<?= $default_domain; ?>/projects/<?= $project_name; ?>">Project Summary</a></p>
            </div>
        </div>
    </body>
</html>
