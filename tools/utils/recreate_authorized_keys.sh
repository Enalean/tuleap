#!/bin/bash

# redo an authorized_keys file if the old one was purged by a gitolite error

CAT='/bin/cat'
#su -

/etc/init.d/codendi stop


#Drop the file:

rm /usr/com/gitolite/.ssh/authorized_keys

#Get the admin ssh key:

public_key=$($CAT /home/codendiadm/.ssh/id_rsa_gl-adm.pub)
echo "$public_key"
#Edit '/usr/com/gitolite/.ssh/authorized_keys':
#authorized_keys= '# gitolite start command="/usr/bin/gl-auth-command id_rsa_gl-adm",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty $public_key # gitolite end'

echo "# gitolite start" >> /usr/com/gitolite/.ssh/authorized_keys
echo "command=\"/usr/bin/gl-auth-command id_rsa_gl-adm\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ""$public_key" >> /usr/com/gitolite/.ssh/authorized_keys
echo "# gitolite end" >> /usr/com/gitolite/.ssh/authorized_keys
#Add:

#'# gitolite start
#command="/usr/bin/gl-auth-command id_rsa_gl-adm",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty'

#Copy the key after the previous text and add at a new line

#'# gitolite end'

#and change the rights:

chown gitolite:gitolite  /usr/com/gitolite/.ssh/authorized_keys

#Then you have to push:

su - codendiadm -c "cd /var/lib/codendi/gitolite/admin && git push"

#Restart the cron:

su - root -c "/etc/init.d/codendi start"