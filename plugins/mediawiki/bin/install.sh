#!/usr/bin/env bash

echo "############################################"
echo "#                                          #"
echo "#       Mediawiki Plugin install           #"
echo "#                                          #"
echo "############################################"

MEDIAWIKI_SRC_DIR="mediawiki-tuleap"

echo "Symlinking for skin"
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
