<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');

$files = glob($GLOBALS['codendi_dir'] . '/tests/js/mocha/test/*.js');

?><html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Mocha Tuleap Test Suite</title>
        <link rel="stylesheet" href="mocha.css" />
    </head>
    <body>
        <div id="mocha"></div>
        <script src="require.js"></script>
        <script>
            
        require(['require', 'chai', 'mocha'], function(require){
            mocha.setup('bdd');
            <?php
                foreach ($files as $file) {
                    include $file;
                }
            ?>;
            mocha.run();
        });
        </script>
    </body>
</html>

