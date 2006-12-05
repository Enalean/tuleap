#! /bin/sh

# convert all locales to utf-8
for po in po/??.po; do
  to="`echo $po|sed -e's/\.po/.utf8.po/'`"
  from=iso-8859-1
  if [ "$po" = "po/ja.po" ]; then from=euc-jp; fi
  if [ "$po" = "po/zh.po" ]; then from=utf-8; 
  else
    iconv -f $from -t utf-8 $po > $to
    mv $po $po.$from
    perl -pi.bak -e"s/charset=$from/charset=utf-8/" $to
    mv $to $po
  fi
done

for po in ??; do
  to="$po.utf8"
  from=iso-8859-1
  if [ "$po" = "ja" ]; then from=euc-jp; fi
  if [ "$po" = "zh" ]; then from=utf-8; else
    if [ "$po" != "po" ]; then
	cp -Ru $po/* $to/
	for pgsrc in $to/pgsrc/*; do
	    case "$pgsrc" in
	    $to/pgsrc/CVS) ;;
	    $to/pgsrc/*.bak) ;;
	    *)     iconv -f $from -t utf-8 $pgsrc > .tmp && mv .tmp $pgsrc
		perl -pi.bak -e"s/charset=$from/charset=utf-8/" $pgsrc
		;;
	    esac
	done
	iconv -f $from -t utf-8 $to/LC_MESSAGES/phpwiki.php > .tmp && mv .tmp $to/LC_MESSAGES/phpwiki.php
	mv $po "$po.$from"
	mv $to $po
    fi
  fi
done

make depend
make
