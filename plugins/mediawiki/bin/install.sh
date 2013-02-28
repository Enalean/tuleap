#get mediawiki (1.20) /!\ needs php 5.3.x
cd /usr/share
wget http://download.wikimedia.org/mediawiki/1.20/mediawiki-1.20.2.tar.gz
tar -xvf mediawiki-1.20.2.tar.gz
mv mediawiki-1.20.2 mediawiki_tuleap

#create folder master. Then, grant the rights to codendiadm user
mkdir -p /var/lib/codendi/plugins/mediawiki/master
chown -R codendiadm:codendiadm /var/lib/codendi/plugins/

# (this bit needs to be done before the next Tuleap package release)
# -open /etc/httpd/conf.d/codendi_aliases.conf
# -locate the 'Plugin directories' section title.
# -paste the following row of code straight after it
# include /etc/httpd/conf.d/plugins/*.inc
# -then
# mkdir /etc/httpd/conf.d/plugins (if not exists)

#add mediawiki httpd plugin
cp /usr/share/codendi/plugins/mediawiki/fusionforge/plugin-mediawiki.inc /etc/httpd/conf.d/plugins/

#Then restart yout httpd service
service httpd restart

# "Install" tuleap theme
ln -s /usr/share/codendi/plugins/mediawiki/mediawiki-skin/Tuleap.php /usr/share/mediawiki_tuleap/skins/

#Go to the mediawiki skin folder
cd /usr/share/mediawiki_tuleap/skins
#############################################
### Potentially doesn't exist, don't care ###
#############################################
cp MonoBook.deps.php Tuleap.deps.php        #
#############################################

cp -r monobook tuleap
ln -s /usr/share/codendi/plugins/mediawiki/mediawiki-skin/TuleapSkin.css /usr/share/mediawiki_tuleap/skins/tuleap