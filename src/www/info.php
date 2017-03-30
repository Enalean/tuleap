<?php
# Must be site admin to access
require_once('pre.php');

HTTPRequest::instance()->checkUserIsSuperUser();

phpinfo();
