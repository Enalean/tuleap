-- Questions:
-- * Do we store a label/title ?
-- * How do we store the link to the ressource
-- * Could we use references like wiki #abcd
-- * How do we manage updates of wiki page that trigger update of document that trigger update of folder... ?

DROP TABLE IF EXISTS plugin_timeline_user;
CREATE TABLE plugin_timeline_user (
    event_id INT UNSIGNED NOT NULL DEFAULT 0,
    user_id INT DEFAULT 0 NOT NULL,
    time    INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (event_id) 
) TYPE = InnoDB;
