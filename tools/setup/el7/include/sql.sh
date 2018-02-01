_sqlCreateDb() {
    ${cat} << EOSQL
CREATE DATABASE ${1} DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
EOSQL
}

_sqlDropDb() {
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
    ${cat} << EOSQL
GRANT ALL PRIVILEGES on *.* to '${mysql_user}'@'${2}' IDENTIFIED BY '${mysql_password}';
GRANT ALL PRIVILEGES ON tuleap.* TO '${1}'@'${2}' IDENTIFIED BY '${3}';
GRANT ALL PRIVILEGES ON \`plugin_mediawiki_%\` . * TO '${1}'@'${2}' IDENTIFIED BY '${3}';
FLUSH PRIVILEGES;
EOSQL
}
