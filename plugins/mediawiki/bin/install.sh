MEDIAWIKI_SRC_DIR="mediawiki-tuleap"

echo "Updating httpd configuration"
cp $INSTALL_DIR/plugins/mediawiki/fusionforge/plugin-mediawiki.inc /etc/httpd/conf.d/plugins/
service httpd restart

#Copy .dist in /etc
cp $INSTALL_DIR/plugins/mediawiki/etc/mediawiki.inc.dist /etc/codendi/plugins/mediawiki/etc/mediawiki.inc

#Copy .dist content at the end of local.inc (get_forge_config do not get variables from mediawiki.inc)

#Symlinking for skin
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/api.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/extensions/ $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/img_auth.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/includes/ $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/index.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/languages/ $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/load.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/maintenance/ $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/opensearch_desc.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/profileinfo.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/redirect.php $INSTALL_DIR/plugins/mediawiki/www
ln -s /usr/share/$MEDIAWIKI_SRC_DIR/thumb.php $INSTALL_DIR/plugins/mediawiki/www
