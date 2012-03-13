#!/bin/bash
PERL='/usr/bin/perl'

[ `id -u` -ne 0 ] && die "Must be root to execute this script"

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  if [ -e $1 ] ; then
    replacement=`echo $3 | sed "s|/|\\\\\/|g"`
    $PERL -pi -e "s/$2/$replacement/g" $1
  fi
}

old_domain=`grep ServerName /etc/httpd/conf/httpd.conf | grep -v '#' | head -1 | cut -d " " -f 2 ;`
echo "Current domain = $old_domain"
read -p "New domain: " new_domain


substitute '/etc/tuleap/conf/local.inc' "$old_domain" "$new_domain" 
substitute '/etc/tuleap/conf.d/codendi_aliases.conf' "$old_domain" "$new_domain" 
substitute '/etc/httpd/conf/httpd.conf' "$old_domain" "$new_domain"
if [ -e '/etc/tuleap/documentation/user_guide/xml/ParametersLocal.dtd' ] ; then
    substitute '/etc/tuleap/documentation/user_guide/xml/ParametersLocal.dtd' "$old_domain" "$new_domain" 
fi
if [ -e '/etc/tuleap/documentation/cli/xml/ParametersLocal.dtd' ] ; then
    substitute '/etc/tuleap/documentation/cli/xml/ParametersLocal.dtd' "$old_domain" "$new_domain" 
fi
if [ -e '/var/named/chroot/var/named/codendi.zone' ] ; then
    substitute '/var/named/chroot/var/named/codendi.zone' "$old_domain" "$new_domain" 
fi
if [ -e '/etc/mail/local-host-names' ] ; then
    substitute '/etc/mail/local-host-names' "$old_domain" "$new_domain" 
fi
if [ -e '/etc/tuleap/plugins/salome/etc/database_salome.inc' ] ; then
    substitute '/etc/tuleap/plugins/salome/etc/database_salome.inc' "$old_domain" "$new_domain"
fi
if [ -e '/etc/tuleap/plugins/IM/etc/jabbex_conf.xml' ] ; then
    substitute '/etc/tuleap/plugins/IM/etc/jabbex_conf.xml' "$old_domain" "$new_domain"
fi


# TODO
# Mailman? /usr/lib/mailman/Mailman/mm_cfg.py + existing mailing lists...
# DB: user email, group http_domain, homepage service

# OpenFire: must re-install jabbex
# OR:
# service openfire stop
# jive-property: xmpp.domain, xmpp.muc.create.jid, xmpp.muc.sysadmin.jid, plugin.subscription.whiteList
# UPDATE mucAffiliation SET jid=replace(jid, '$old_domain', '$new_domain');
# UPDATE mucConversationLog SET sender=replace(sender, '$old_domain', '$new_domain');
# UPDATE mucMember SET jid=replace(jid, '$old_domain', '$new_domain');
# UPDATE pubsubAffiliation SET jid=replace(jid, '$old_domain', '$new_domain');
# UPDATE pubsubAffiliation SET nodeID=replace(nodeID, '$old_domain', '$new_domain');
# UPDATE pubsubAffiliation SET serviceID=replace(serviceID, '$old_domain', '$new_domain');
# UPDATE pubsubDefaultConf SET serviceID=replace(serviceID, '$old_domain', '$new_domain');
# UPDATE pubsubItem SET nodeID=replace(nodeID, '$old_domain', '$new_domain');
# UPDATE pubsubItem SET serviceID=replace(serviceID, '$old_domain', '$new_domain');
# UPDATE pubsubItem SET jid=replace(jid, '$old_domain', '$new_domain');
# UPDATE pubsubNode SET serviceID=replace(serviceID, '$old_domain', '$new_domain');
# UPDATE pubsubNode SET nodeID=replace(nodeID, '$old_domain', '$new_domain');
# UPDATE pubsubNode SET parent=replace(parent, '$old_domain', '$new_domain');
# UPDATE pubsubNode SET creator=replace(creator, '$old_domain', '$new_domain');
# UPDATE pubsubSubscription SET serviceID=replace(serviceID, '$old_domain', '$new_domain');
# UPDATE pubsubSubscription SET nodeID=replace(nodeID, '$old_domain', '$new_domain');
# UPDATE pubsubSubscription SET jid=replace(jid, '$old_domain', '$new_domain');
# UPDATE pubsubSubscription SET owner=replace(owner, '$old_domain', '$new_domain');


