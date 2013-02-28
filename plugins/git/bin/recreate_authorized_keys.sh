#!/bin/bash

# Copyright (c) Enalean, 2013. All Rights Reserved.
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

# redo an authorized_keys file if the old one was purged by a gitolite error
# You need to be root when executign this script

CAT='/bin/cat'
/etc/init.d/codendi stop

#The location of gitolite authorized keys file depends on gitolite version
if [ -f /usr/com/gitolite/.ssh/authorized_keys ]
then
   DIR='/usr/com'
else
   DIR='/var/lib'
fi

rm $DIR/gitolite/.ssh/authorized_keys

public_key=$($CAT /home/codendiadm/.ssh/id_rsa_gl-adm.pub)

echo "Recreate authorized_keys and add gl-adm public key"

#Edit gitolite/.ssh/authorized_keys:
echo "# gitolite start" >> $DIR/gitolite/.ssh/authorized_keys
echo "command=\"/usr/bin/gl-auth-command id_rsa_gl-adm\",no-port-forwarding,no-X11-forwarding,no-agent-forwarding,no-pty ""$public_key" >> $DIR/gitolite/.ssh/authorized_keys
echo "# gitolite end" >> $DIR/gitolite/.ssh/authorized_keys

echo "authorized_keys correctly filled"

chown gitolite:gitolite  $DIR/gitolite/.ssh/authorized_keys

# Push the gitolite admin repository to refill the authorized_keys file
su - codendiadm -c "cd /var/lib/codendi/gitolite/admin && git push"

/etc/init.d/codendi start