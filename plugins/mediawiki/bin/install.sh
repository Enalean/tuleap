MEDIAWIKI_SRC_DIR="mediawiki_tuleap"

echo "Updating httpd configuration"
cp /usr/share/codendi/plugins/mediawiki/fusionforge/plugin-mediawiki.inc /etc/httpd/conf.d/plugins/
service httpd restart

# "Install" tuleap theme
ln -s /usr/share/codendi/plugins/mediawiki/mediawiki-skin/Tuleap.php /usr/share/$MEDIAWIKI_SRC_DIR/skins/

#Go to the mediawiki skin folder
cd /usr/share/$MEDIAWIKI_SRC_DIR/skins
if [ -f MonoBook.deps.php ]
then
 cp MonoBook.deps.php Tuleap.deps.php
fi

cp -r monobook tuleap
ln -s /usr/share/codendi/plugins/mediawiki/mediawiki-skin/TuleapSkin.css /usr/share/$MEDIAWIKI_SRC_DIR/skins/tuleap/.