#!/bin/bash

# redo an authorized_keys file if the old one was purged by a gitolite error
# You need to be root when executign this script

CAT='/bin/cat'
/etc/init.d/codendi stop

#Drop the file:

rm /usr/com/gitolite/.ssh/authorized_keys

#Get the admin ssh key:

public_key=$($CAT /home/codendiadm/.ssh/id_rsa_gl-adm.pub)

#Provide feedback
echo "Recreate authorized_keys and add gl-adm public key"

#Edit '/usr/com/gitolite/.ssh/authorized_keys':

echo "# gitolite start" >> /usr/com/gitolite/.ssh/authorized_keys
echo "command=\"/usr/bin/gl-auth-command id_rsa_gl-adm\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ""$public_key" >> /usr/com/gitolite/.ssh/authorized_keys
echo "# gitolite end" >> /usr/com/gitolite/.ssh/authorized_keys

#Provide feedback
echo "authorized_keys correctly filled"

#Change the rights:

chown gitolite:gitolite  /usr/com/gitolite/.ssh/authorized_keys

#Then you have to push:

su - codendiadm -c "cd /var/lib/codendi/gitolite/admin && git push"

#Restart the cron:

su - root -c "/etc/init.d/codendi start"