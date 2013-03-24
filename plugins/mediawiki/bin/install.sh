MEDIAWIKI_SRC_DIR="mediawiki_tuleap_delete"

#get mediawiki (1.20) /!\ needs php 5.3.x
echo "Start download Mediawiki 1.20"
cd /usr/share
wget http://download.wikimedia.org/mediawiki/1.20/mediawiki-1.20.2.tar.gz
tar -xvf mediawiki-1.20.2.tar.gz
mv mediawiki-1.20.2 $MEDIAWIKI_SRC_DIR
echo "Mediawiki fully downloaded into $MEDIAWIKI_SRC_DIR"

echo "Initializing Mediawiki repository"
mkdir -p /var/lib/codendi/plugins/mediawiki/master
chown -R codendiadm:codendiadm /var/lib/codendi/plugins/

echo "Updating httpd configuration"
cp /usr/share/codendi/plugins/mediawiki/fusionforge/plugin-mediawiki.inc /etc/httpd/conf.d/plugins/
service httpd restart

# "Install" tuleap theme
ln -s /usr/share/codendi/plugins/mediawiki/mediawiki-skin/Tuleap.php /usr/share/$MEDIAWIKI_SRC_DIR/skins/

#Go to the mediawiki skin folder
cd /usr/share/mediawiki_tuleap/skins
if [ -f MonoBook.deps.php ]
then
 cp MonoBook.deps.php Tuleap.deps.php
fi

cp -r monobook tuleap
ln -s /usr/share/codendi/plugins/mediawiki/mediawiki-skin/TuleapSkin.css /usr/share/$MEDIAWIKI_SRC_DIR/skins/tuleap/.