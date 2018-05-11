_sqlCreateDb() {
    # ${1}: database name

    ${cat} << EOSQL
CREATE DATABASE ${1} DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
EOSQL
}

_sqlDropDb() {
    # ${1}: database name

    ${cat} << EOSQL
DROP DATABASE ${1};
EOSQL
}

_sqlShowDb() {
    ${cat} << EOSQL
SHOW DATABASES;
EOSQL
}

_sqlShowMode() {
    ${cat} << EOSQL
SHOW VARIABLES LIKE 'sql_mode';
EOSQL
}

_sqlAllPrivileges() {
    # ${1}: user
    # ${2}: web server hostname
    # ${3}: password

    ${cat} << EOSQL
GRANT ALL PRIVILEGES ON tuleap.* TO '${1}'@'${2}' IDENTIFIED BY '${3}';
FLUSH PRIVILEGES;
EOSQL
}

_sqlDbauthuserPrivileges() {
    # ${1}: web server hostname
    # ${2}: password

    ${cat} << EOSQL
GRANT SELECT ON tuleap.user TO 'dbauthuser'@'${1}' IDENTIFIED BY '${2}';
GRANT SELECT ON tuleap.groups  TO 'dbauthuser'@'${1}';
GRANT SELECT ON tuleap.user_group TO 'dbauthuser'@'${1}';
GRANT SELECT,UPDATE ON tuleap.svn_token TO 'dbauthuser'@'${1}';
FLUSH PRIVILEGES;
EOSQL
}
