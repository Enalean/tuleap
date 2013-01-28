#!/bin/sh

# @PLUGIN_PATH@
# @SOURCE_PATH@
# @DATA_PATH@

PLUGIN_PATH=/usr/share/codendi/plugins
SOURCE_PATH=/usr/share/codendi/src
DATA_PATH=/var/lib/codendi

scriptdir=`dirname $0`
absolutedir=`cd $scriptdir;pwd`
plugindir=`dirname $absolutedir`

if [ -e /usr/share/codendi ]
then 
	OLDPACKAGE=codendi
else
	OLDPACKAGE=gforge
fi

LINKS=$plugindir/packaging/links/plugin-mediawiki
cat $LINKS | sed "s%@OLDPACKAGE@%$OLDPACKAGE%g" | sed "s%@PLUGIN_PATH@%$PLUGIN_PATH%g" | sed "s%@SOURCE_PATH@%$SOURCE_PATH%g" | sed "s%@DATA_PATH@%$DATA_PATH%g" | while read src dest
do
	if [ ! -e /$src ]
	then
		newsrc=`echo $src | sed 's/mediawiki/mediawiki115/'`
		if [ -e "/$newsrc" ]
		then
			src=$newsrc
		fi
	fi
	if [ -e "/$src" ]
	then
		if [ -e /usr/share/codendi ]
		then
			dest=`echo $dest | sed 's/gforge/codendi/'`
		fi
		if [ ! -e /$dest ]
		then
			echo "Symlinking /$dest -> /$src"
			ln -s /$src /$dest
		fi
	fi
done
