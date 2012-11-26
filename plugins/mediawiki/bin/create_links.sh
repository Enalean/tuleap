#!/bin/sh

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
cat $LINKS | sed "s/@OLDPACKAGE@/$OLDPACKAGE/g" | while read src dest
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
