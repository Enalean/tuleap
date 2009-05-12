#!/bin/sh

#echo "This Is A script... Running Under %POST Of Spec..." > ~/toto.log
sed -i "s|%$2|$3|g" $1;
