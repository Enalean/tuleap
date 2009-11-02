#!/bin/bash

MYSQL_PARAMS=""
TMP_DUMP_DIR="/var/tmp"

#########################################################
# 
# Codendi migration from openfire 3.5.2 to openfire 3.6.4
#
#


# 1. Check that all required files are here.
echo "Checking openfire version"
rpm -q openfire-${OPENFIRE_VERSION}* | grep 3.6.4-1 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
	echo "Current Openfire version is 3.6.4 no need to upgrade. Bye!";
	exit 1;
fi
rpm -q openfire-${OPENFIRE_VERSION}* | grep 3.5.2-1 2>/dev/null 1>&2
if [ $? -eq 0 ]; then
	echo "Required Openfire version is 3.5.2. Found $?. Exit.";
	exit 1;
fi
if [ ! -f "openfire-3.6.4-1.i386.rpm" ]; then
	echo "Error: File openfire-3.6.4-1.i386.rpm is missing";
	exit 1;
fi
if [ ! -f "codendi_auth.jar" ]; then
	echo "Error: File codendi_auth.jar is missing";
	exit 1;
fi

if [ ! -f "monitoring.jar" ]; then
	echo "Error: File monitoring.jar is missing";
	exit 1;
fi
if [ ! -f "presence.jar" ]; then
	echo "Error: File presence.jar is missing";
	exit 1;
fi
if [ ! -f "subscription.jar" ]; then
	echo "Error: File subscription.jar is missing";
	exit 1;
fi

# 2. retrieve openfire DB password
OPENFIRE_DB_PASSWORD="`php -r '\$jive = new SimpleXmlElement(file_get_contents(\"/opt/openfire/conf/openfire.xml\")); echo \$jive->database->defaultProvider->password;'`"
echo "Openfire DB password is: $OPENFIRE_DB_PASSWORD"


# 3. Stop Openfire
service openfire stop

# 4. Backup the Openfire installation directory.
echo "Backing up the Openfire installation directory..."
cp -r /opt/openfire /opt/openfire.bak

# 5. Backup the Openfire database. Note that the embedded database is backed up in step 2.
echo "Backing up the Openfire database..."
pass_opt=""
# See if MySQL root account is password protected
mysqlshow $MYSQL_PARAMS 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -s -p "Existing DB is password protected. What is the Mysql root password?: " old_passwd
    echo
    mysqlshow $MYSQL_PARAMS --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"
mysqldump $MYSQL_PARAMS --max_allowed_packet=512M -u root $pass_opt openfire > /tmp/dump.openfire.sql

# 6. Install the new RPM. Execute rpm -Uvf openfire-x.x.x-x.i386.rpm to update your current install
echo "Installing the new openfire RPM"
rpm -Uvf openfire-3.6.4-1.i386.rpm
# 
# Preparing packages for installation...
# openfire-3.6.4-1
# warning: /opt/openfire/conf/openfire.xml created as /opt/openfire/conf/openfire.xml.rpmnew
#
echo "Please ignore warning message above"

# 7. Start Openfire. (this will apply the database upgrades)
service openfire start
sleep 5

# 8. Stop openfire
service openfire stop

# 9. Update openfire database:
echo "Updating openfire configuration"
mysql $MYSQL_PARAMS -u openfireadm --password=$OPENFIRE_DB_PASSWORD openfire -e "INSERT INTO openfire.ofProperty (name, propValue) VALUES ('jdbcAuthProvider.codendiUserSessionIdSQL', 'SELECT session_hash FROM session WHERE session.user_id = (SELECT user_id FROM user WHERE user.user_name = ?)');"

# 10. Copy new codendi_auth jar (set daemon as owner of the file)
echo "Copying codendi_auth.jar into /opt/openfire/lib"
rm -f /opt/openfire/lib/codendi_auth.jar
cp codendi_auth.jar /opt/openfire/lib/.
chown daemon:daemon /opt/openfire/lib/codendi_auth.jar

# 11. Install new plugins:
#     - monitoring 1.1.1 (1.0.1 => 1.1.1)
#     - search 1.4.3 (1.4.2 => 1.4.3)
#     - subscription (idem)
#     - presence (idem)
echo "Installing openfire plugins..."
rm -rf /opt/openfire/plugins/monitoring/
rm -rf /opt/openfire/plugins/presence/
rm -rf /opt/openfire/plugins/subscription/
rm -f /opt/openfire/plugins/monitoring.jar
rm -f /opt/openfire/plugins/presence.jar
rm -f /opt/openfire/plugins/subscription.jar

cp monitoring.jar presence.jar search.jar subscription.jar /opt/openfire/plugins/.

# 12. Start Openfire
service openfire start
sleep 5

echo "Migration done!"
