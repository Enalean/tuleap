# $Id: Makefile,v 1.9 2005/08/07 09:15:38 rurban Exp $
# user-definable settings:
# for mysqladmin
DBADMIN_USER=root
DBADMIN_PASS=secret

# etags (GNU Emacs 21.5.x)
#ETAGS_STDIN = /usr/bin/etags -L -
# etags (GNU Emacs 21.4.15)
ETAGS_STDIN = /usr/bin/etags -

DB_SQLITE_DBFILE = /tmp/phpwiki-sqlite.db

# ****************************************************************************
# get db params from config/config.ini

#DATABASE_TYPE=SQL
DATABASE_TYPE := $(shell config/make-dbhelper.pl -v=DATABASE_TYPE config/config.ini)
PROCESS_DSN=0
ifeq (${DATABASE_TYPE},SQL)
  PROCESS_DSN=1
else
  ifeq (${DATABASE_TYPE},ADODB) 
    PROCESS_DSN=1
  endif
endif

ifeq (${PROCESS_DSN},1)
  # get db params from config/config.ini DATABASE_DSN setting (only if SQL or ADODB)
  DATABASE_DSN := $(shell config/make-dbhelper.pl -v=DATABASE_DSN config/config.ini)
  #DB_DBTYPE=mysql
  DB_DBTYPE := $(word 1,${DATABASE_DSN})
  #DB_DB=phpwiki
  DB_DB   := $(word 2,${DATABASE_DSN})
  #DB_USER=wikiuser
  DB_USER := $(word 3,${DATABASE_DSN})
  #DB_PASS=
  DB_PASS := $(word 4,${DATABASE_DSN})
  #Todo: read optional DBADMIN_USER and DBADMIN_PASS settings from config.ini
  DBADMIN_OPTS=-u$(DBADMIN_USER) -p$(DBADMIN_PASS)
else
  DB_DBTYPE=${DATABASE_TYPE}
endif

# ****************************************************************************
PHP_SRC := $(wildcard *.php ./lib/*.php ./lib/WikiDB/*.php ./lib/plugin/*.php)

.PHONY: all install locale mysql pqsql sqlite dbtest install-config

all:  TAGS

TAGS:  $(PHP_SRC)
	if [ -f $@ ]; then /usr/bin/mv -f $@ $@~; fi
#	etags $(PHP_SRC)
	/usr/bin/find . \( -type d -regex '\(^\./lib/pear\)\|\(^\./lib/WikiDB/adodb\)\|\(^\./lib/nusoap\)\|\(^\./locale/.*/LC_MESSAGES\)' \) -prune -o -type f -name \*.php | grep .php | $(ETAGS_STDIN)

TAGS.full:  $(PHP_SRC)
	if [ -f $@ ]; then /usr/bin/mv -f $@ $@~; fi
	/usr/bin/find . -name \*.php -o -name \*.tmpl | $(ETAGS_STDIN) --langmap="HTML:.tmpl" -f $@
# older etags needed this:
#	/usr/bin/find . -type f -name \*.php -o -name \*.tmpl | etags - -o $@

locale: 
	cd locale
	make

install:	install-config install-database

install-config: config/config.ini

config/config.ini: config/config-dist.ini
	cp config/config-dist.ini $@
	echo "You must edit config/config.ini, at least set the ADMIN_PASSWD"
	${EDITOR} $@

# helpers for database installation
install-database: ${DB_DBTYPE}

dba:

cvs:

# maybe setup permissions
file:

dbtest:
	echo DATABASE_TYPE=${DATABASE_TYPE} DB_DBTYPE=${DB_DBTYPE} DB_DB=$(DB_DB) DB_USER=${DB_USER} DB_PASS=${DB_PASS} DBADMIN_OPTS=$(DBADMIN_OPTS)

# initialize the database
# TODO: compare /var/mysql/data/$(DB_DB) timestamp against schemas/mysql.sql
mysql:
	mysqladmin $(DB_OPTS) create $(DB_DB)
	mysql $(DB_OPTS) -e "GRANT select,insert,update,delete,lock tables ON $(DB_DB).* \
TO $(DB_USER)@localhost IDENTIFIED BY '$(DB_PASS)';"
	mysql $(DB_OPTS) $(DB_DB) < schemas/mysql-initialize.sql

# initialize the database
pqsql:
	su postmaster
	createdb $(DB_DB)
	ifeq ($(DB_PASS),"")
	  createuser -D -A -P $(DB_USER)
	else
	  createuser -D -A $(DB_USER)
	endif
	psql $(DB_DB) -f schemas/psql-initialize.sql
	logout

# initialize the database
sqlite:	$(DB_SQLITE_DBFILE)
	sqlite $(DB_SQLITE_DBFILE) < schemas/sqlite-initialize.sql

# update the database
${DB_SQLITE_DBFILE}: schemas/sqlite.sql
	echo ".dump" | sqlite ${DB_SQLITE_DBFILE} > dump.sql
	mv ${DB_SQLITE_DBFILE} ${DB_SQLITE_DBFILE}.old
	sqlite $(DB_SQLITE_DBFILE) < dump.sql
