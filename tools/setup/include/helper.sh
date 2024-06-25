_usageSetup() {
    ${printf} "%s\n"   "Usage: ${script_name} [OPTION]..."
    ${printf} "%s\n\n" "OPTIONS (** is mandatory):"
    ${printf} "%s\n\n" "  --server-name=<fqdn/host>      fully qualified domain name/hostname**"
    ${printf} "%s\n"   "  --mysql-server=<host>          hostname/IP of mysql server**"
    ${printf} "%s\n"   "  --mysql-port=<integer>         port number to use for connection (default: 3306)"
    ${printf} "%s\n"   "  --mysql-user=<user>            user to create database and grant permissions (default: root)"
    ${printf} "%s\n\n" "  --mysql-password=<password>    password to use when connecting to server"
    ${printf} "%s\n"   "  --disable-auto-passwd          do not automaticaly generate random passwords"
    ${printf} "%s\n"   "  --disable-mysql-conf           do not modify my.cnf"
    ${printf} "%s\n\n" "  --disable-check-server-name    do not check server name"
    ${printf} "%s\n\n" "  -c, --configure                configure new plugins installed"
    ${printf} "%s\n\n" "  -r, --reinstall                reinstall tuleap"
    ${printf} "%s\n\n" "  -y, --assumeyes                your answer is 'yes' for any questions (default: no)"
    ${printf} "%s\n\n" "  -d, --debug                    enable xtrace"
    ${printf} "%s\n"   "  -h, --help                     display this help and exit"
    exit 1
}
