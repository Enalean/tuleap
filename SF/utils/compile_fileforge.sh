#!/bin/sh
# This script must be executed from root account !!
# LJ 2000-12-06
#
gcc fileforge.c -o fileforge
chown root.root fileforge
chmod u+s fileforge
mv fileforge /usr/local/bin

