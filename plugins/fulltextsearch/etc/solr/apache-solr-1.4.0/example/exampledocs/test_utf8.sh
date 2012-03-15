#!/bin/sh
# Licensed to the Apache Software Foundation (ASF) under one or more
# contributor license agreements.  See the NOTICE file distributed with
# this work for additional information regarding copyright ownership.
# The ASF licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

#Test script to tell if the server is accepting UTF-8
#The python writer currently escapes non-ascii chars, so it's good for testing

URL=http://localhost:8983/solr/select

if [ ! -z $1 ]; then
  URL=$1
fi

curl "$URL/select?q=hello&params=explicit&wt=python" 2> /dev/null | grep 'hello' > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "Solr server is up."
else
  echo "ERROR: Solr is not up."
  exit 1
fi

curl "$URL/select?q=h%C3%A9llo&echoParams=explicit&wt=python" 2> /dev/null | grep 'h\\u00e9llo' > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "HTTP GET is accepting UTF-8"
else
  echo "ERROR: HTTP GET is not accepting UTF-8"
fi

curl $URL/select --data-binary 'q=h%C3%A9llo&echoParams=explicit&wt=python' -H 'Content-type:application/x-www-form-urlencoded; charset=UTF-8' 2> /dev/null | grep 'h\\u00e9llo' > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "HTTP POST is accepting UTF-8"
else
  echo "ERROR: HTTP POST is not accepting UTF-8"
fi

curl $URL/select --data-binary 'q=h%C3%A9llo&echoParams=explicit&wt=python' 2> /dev/null | grep 'h\\u00e9llo' > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "HTTP POST defaults to UTF-8"
else
  echo "HTTP POST does not default to UTF-8"
fi


#A codepoint outside of the BMP
CODEPOINT='\\U00100058'
#URL encoded UTF8 of the codepoint
URL_UTF8='%F4%80%81%98'
#expected return of the python writer (currently uses UTF-16 surrogates)
EXPECTED='\\udbc0\\udc58'

curl "$URL/select?q=$URL_UTF8&echoParams=explicit&wt=python" 2> /dev/null | grep $EXPECTED > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "HTTP GET is accepting UTF-8 beyond the basic multilingual plane"
else
  echo "ERROR: HTTP GET is not accepting UTF-8 beyond the basic multilingual plane"
fi

curl $URL/select --data-binary "q=$URL_UTF8&echoParams=explicit&wt=python"  -H 'Content-type:application/x-www-form-urlencoded; charset=UTF-8' 2> /dev/null | grep $EXPECTED > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "HTTP POST is accepting UTF-8 beyond the basic multilingual plane"
else
  echo "ERROR: HTTP POST is not accepting UTF-8 beyond the basic multilingual plane"
fi

curl "$URL/select?q=$URL_UTF8&echoParams=explicit&wt=python" --data-binary '' 2> /dev/null | grep $EXPECTED > /dev/null 2>&1
if [ $? = 0 ]; then
  echo "HTTP POST + URL params is accepting UTF-8 beyond the basic multilingual plane"
else
  echo "ERROR: HTTP POST + URL params is not accepting UTF-8 beyond the basic multilingual plane"
fi

