<?php

//This file was added to allow adding utilities functions in top of pre.php

function util_is_php_version_equal_or_greater_than_53() {
    return version_compare(phpversion(), '5.3', '>=');
}
?>