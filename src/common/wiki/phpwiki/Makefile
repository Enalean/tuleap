# gnu make (also with cygwin) version
PHP_SRC := $(wildcard *.php ./lib/*.php ./lib/WikiDB/*.php ./lib/plugin/*.php)

all:  TAGS locale

TAGS:  $(PHP_SRC)
#	etags $(PHP_SRC)
	if [ -f TAGS ]; then /usr/bin/mv -f TAGS TAGS~; fi
	/usr/bin/find . -name \*.php -exec etags -a '{}' \;

locale: 
	make -f locale/Makefile

# TODO: get db params from index.php and/or configurator.php
DB_ADMINUSER=root
DB_ADMINPASS=
DB_DB=phpwiki
DB_USER=wikiuser
DB_PASS=

DB_OPTS=-u$(DB_ADMINUSER) -p$(DB_ADMINPASS)

mysql:
	mysqladmin $(DB_OPTS) create $(DB_DB)
	mysql $(DB_OPTS) -e "GRANT select, insert, update, delete ON $(DB_DB).* \
TO $(DB_USER)@localhost IDENTIFIED BY '$(DB_PASS)';"
	mysql $(DB_OPTS) $(DB_DB) <schemas/mysql.SQL

psql:
	su postmaster
	createdb $(DB_DB)
ifeq ($(DB_PASS),"")
	createuser -D -A -P $(DB_USER)
else
	createuser -D -A $(DB_USER)
endif
	psql $(DB_DB) -f schemas/schema.psql
	logout

