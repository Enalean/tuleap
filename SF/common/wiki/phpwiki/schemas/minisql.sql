# $Id$

drop table wiki\g
drop table wikipages\g
drop table archive\g
drop table archivepages\g

drop table wikilinks\g
drop table hottopics\g
drop table hitcount\g
drop table wikiscore\g

# metadata about the page

CREATE TABLE wiki (
        pagename CHAR(100) NOT NULL,
        version INT NOT NULL,
        flags INT NOT NULL,
        author CHAR(100),
        lastmodified INT NOT NULL,
        created INT NOT NULL,
        refs TEXT(100)
        )
\g

CREATE UNIQUE INDEX wiki_index ON wiki (pagename)
\g

# archive for page metadata

CREATE TABLE archive (
        pagename CHAR(100) NOT NULL,
        version INT NOT NULL,
        flags INT NOT NULL,
        author CHAR(100),
        lastmodified INT NOT NULL,
        created INT NOT NULL,
        refs TEXT(100)
        )
\g

CREATE UNIQUE INDEX archive_index ON archive (pagename, version)
\g


# table for the pages themselves... stored in lines

CREATE TABLE wikipages (
	pagename CHAR(100) NOT NULL,
        lineno INT NOT NULL,
        line CHAR(128)
)
\g

CREATE UNIQUE INDEX wp_idx ON wikipages (pagename, lineno)
\g


# archive of page lines

CREATE TABLE archivepages (
	pagename CHAR(100) NOT NULL,
        lineno INT NOT NULL,
        line CHAR(128)
)
\g

CREATE UNIQUE INDEX ap_idx ON archivepages (pagename, lineno)
\g



# tables below are not yet used

CREATE TABLE wikilinks (
        frompage CHAR(100) NOT NULL,
        topage CHAR(100) NOT NULL
        )
\g

CREATE UNIQUE INDEX wikilinks_index ON wikilinks (frompage, topage)
\g

CREATE TABLE hottopics (                
        pagename CHAR(100) NOT NULL,
        lastmodified INT NOT NULL
        )
\g

CREATE UNIQUE INDEX hottopics_index ON hottopics (pagename, lastmodified)
\g

CREATE TABLE hitcount (                 
        pagename CHAR(100) NOT NULL, 
        hits INT NOT NULL
        )
\g

CREATE UNIQUE INDEX hitcount_index ON hitcount (pagename)
\g

CREATE TABLE wikiscore (
        pagename CHAR(100) NOT NULL,
        score INT NOT NULL
        )
\g

CREATE UNIQUE INDEX hitcount_index ON wikiscore (pagename)
\g
