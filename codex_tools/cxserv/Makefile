#
# Copyright (c) 2003, Xerox Corporation, SSTC.  All rights reserved.
#
# $Id$
#
DEST=/usr/local/bin
SOURCES=cxserv.c
OBJECTS=$(SOURCES:.c=.o)
PROGRAM=cxserv

CFLAGS= -Os

all: cxserv

cxserv: $(OBJECTS)
	$(LINK.c) -o $(PROGRAM) $(OBJECTS)

install: $(PROGRAM)
	install --owner=root --mode=u=rwxs,g=rx,o=rx $(PROGRAM) $(DEST)
clean:
	rm -f $(OBJECTS) $(PROGRAM) *~
