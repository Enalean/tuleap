#!/bin/sh

/usr/lib/rpm/perl.req $* | sed -e '/perl(Config::IniFiles)/d'
