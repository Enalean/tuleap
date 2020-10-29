#!/bin/bash

echo "Installing and activating TTM plugin"
su -c "PHP='$PHP_CLI' /usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php testmanagement" -l codendiadm
