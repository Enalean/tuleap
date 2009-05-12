#!/bin/sh

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
	sed -i "s|$2|$3|g" $1
}


# Ask for domain name and other installation parameters
read -p "CodeX Domain name: " sys_default_domain
read -p "Your Company short name (e.g. Xerox): " sys_org_name
read -p "Your Company long name (e.g. Xerox Corporation): " sys_long_org_name
read -p "Codex Server fully qualified machine name: " sys_fullname
read -p "Codex Server IP address: " sys_ip_address
read -p "LDAP server name: " sys_ldap_server
read -p "Windows domain (Samba): " sys_win_domain
read -p "Activate user shell accounts? [y|n]:" active_shell
read -p "Generate a self-signed SSL certificate to enable HTTPS support? [y|n]:" create_ssl_certificate
read -p "Disable sub-domain management (no DNS delegation)? [y|n]:" disable_subdomains

# Ask for user passwords
rt_passwd="a"; rt_passwd2="b";
while [ "$rt_passwd" != "$rt_passwd2" ]; do
    read -s -p "Password for MySQL root: " rt_passwd
    echo
    read -s -p "Retype MySQL root password: " rt_passwd2
    echo
done

codexadm_passwd="a"; codexadm_passwd2="b";
while [ "$codexadm_passwd" != "$codexadm_passwd2" ]; do
    read -s -p "Password for user codexadm: " codexadm_passwd
    echo
    read -s -p "Retype codexadm password: " codexadm_passwd2
    echo
done

mm_passwd="a"; mm_passwd2="b";
while [ "$mm_passwd" != "$mm_passwd2" ]; do
    read -s -p "Password for user mailman: " mm_passwd
    echo
    read -s -p "Retype mailman password: " mm_passwd2
    echo
done

slm_passwd="a"; slm_passwd2="b";
while [ "$slm_passwd" != "$slm_passwd2" ]; do
    read -s -p "Password for Salome DB user: " slm_passwd
    echo
    read -s -p "Retype password for Salome DB user: " slm_passwd2
    echo
done

openfire_passwd="a"; openfire_passwd2="b";
while [ "$openfire_passwd" != "$openfire_passwd2" ]; do
    read -s -p "Password for Openfire DB user: " openfire_passwd
    echo
    read -s -p "Retype password for Openfire DB user: " openfire_passwd2
    echo
done


substitute 'codex/codex.spec' '%sys_default_domain%' "$sys_default_domain" 
substitute 'codex/codex.spec' '%sys_org_name%' "$sys_org_name" 
substitute 'codex/codex.spec' '%sys_long_org_name%' "$sys_long_org_name" 
substitute 'codex/codex.spec' '%sys_fullname%' "$sys_fullname" 
substitute 'codex/codex.spec' '%sys_ip_address%' "$sys_ip_address" 
substitute 'codex/codex.spec' '%sys_ldap_server%' "$sys_ldap_server" 
substitute 'codex/codex.spec' '%sys_win_domain%' "$sys_win_domain" 
substitute 'codex/codex.spec' '%active_shell%' "$active_shell" 
substitute 'codex/codex.spec' '%create_ssl_certificate%' "$create_ssl_certificate" 
substitute 'codex/codex.spec' '%disable_subdomains%' "$disable_subdomains" 


substitute 'codex/codex.spec' '%rt_passwd%' "$rt_passwd" 
substitute 'codex/codex.spec' '%codexadm_passwd%' "$codexadm_passwd" 
substitute 'codex/codex.spec' '%mm_passwd%' "$mm_passwd" 
substitute 'codex/codex.spec' '%slm_passwd%' "$slm_passwd" 
substitute 'codex/codex.spec' '%openfire_passwd%' "$openfire_passwd" 


