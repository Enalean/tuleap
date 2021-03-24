#!/usr/bin/env bash

su -c "PHP='$PHP_CLI' /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php roadmap" -l codendiadm
tuleap config-set feature_flag_plugin_roadmap_display_underconstruction_widget 1
