#!/bin/bash

echo "Installing and activating TTM plugin"
su -c "/usr/share/tuleap/src/utils/php-launcher.sh /usr/share/tuleap/tools/utils/admin/activate_plugin.php testmanagement" -l codendiadm
