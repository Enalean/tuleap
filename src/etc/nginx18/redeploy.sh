#!/usr/bin/env bash

TULEAP_BASE_DIR="/usr/share/tuleap"
NGINX_CONFIG_DIR="/etc/opt/rh/rh-nginx18/nginx"
TULEAP_D="$NGINX_CONFIG_DIR/conf.d/tuleap.d"
TULEAP_PLUGINS="$NGINX_CONFIG_DIR/conf.d/tuleap-plugins"

if [ ! -d "$TULEAP_D" ]; then
    mkdir -p "$TULEAP_D"
fi

if [ ! -d "$TULEAP_PLUGINS" ]; then
    mkdir -p "$TULEAP_PLUGINS";
fi

TULEAP_D_BASE="$TULEAP_BASE_DIR/src/etc/nginx18/tuleap.d"
for file in $(/bin/ls "$TULEAP_D_BASE"); do
    /bin/cp -f "$TULEAP_D_BASE/$file" "$TULEAP_D"
done

/bin/cp -f "$TULEAP_BASE_DIR/src/etc/nginx18/tuleap-apache.proxy" "$NGINX_CONFIG_DIR/conf.d"

for plugin in $(/bin/ls $TULEAP_BASE_DIR/plugins); do
    PLUGIN_CONF_FILE="$TULEAP_BASE_DIR/plugins/$plugin/etc/nginx18/$plugin.conf"
    if [ -f "$PLUGIN_CONF_FILE" ]; then
        /bin/cp -f $PLUGIN_CONF_FILE $TULEAP_PLUGINS
    else
        if [ -d "$TULEAP_BASE_DIR/plugins/$plugin/www" ]; then
            sed -e "s/%name%/$plugin/g" "$TULEAP_BASE_DIR/src/etc/nginx18/plugin.conf.dist" > $TULEAP_PLUGINS/$plugin.conf
        fi
    fi
done

if [ "$1" != "--no-restart" ]; then
    service rh-nginx18-nginx reload
fi
