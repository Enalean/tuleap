DROP TABLE IF EXISTS pfo_role_class;
CREATE TABLE pfo_role_class (
       class_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
       class_name varchar(100) DEFAULT '' NOT NULL,
       CONSTRAINT pfo_role_class_name_unique UNIQUE (class_name)
) ;

DROP TABLE IF EXISTS pfo_role;
CREATE TABLE pfo_role (
       role_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
       role_name varchar(100) NOT NULL,
       role_class integer DEFAULT 1 NOT NULL REFERENCES pfo_role_class (class_id),
       home_group_id integer,
       is_public boolean DEFAULT false NOT NULL,
       old_role_id integer DEFAULT 0 NOT NULL,
       CONSTRAINT pfo_role_name_unique UNIQUE (role_id, role_name)
) ;

DROP TABLE IF EXISTS role_project_refs;
CREATE TABLE role_project_refs (
       role_id integer DEFAULT 0 NOT NULL REFERENCES pfo_role,
       group_id integer DEFAULT 0 NOT NULL REFERENCES groups,
       CONSTRAINT role_project_refs_unique UNIQUE (role_id, group_id)
) ;

DROP TABLE IF EXISTS pfo_role_setting;
CREATE TABLE pfo_role_setting (
       role_id integer DEFAULT 0 NOT NULL REFERENCES pfo_role,
       section_name varchar(100) DEFAULT '' NOT NULL,
       ref_id integer DEFAULT 0 NOT NULL,
       perm_val integer DEFAULT 0 NOT NULL,
       CONSTRAINT pfo_role_setting_unique UNIQUE (role_id, section_name, ref_id)
) ;

DROP TABLE IF EXISTS pfo_user_role;
CREATE TABLE pfo_user_role (
       user_id integer DEFAULT 0 NOT NULL REFERENCES users,
       role_id integer DEFAULT 0 NOT NULL REFERENCES pfo_role,
       CONSTRAINT pfo_user_role_unique UNIQUE (user_id, role_id)
) ;

INSERT INTO pfo_role_class (class_id, class_name) VALUES (1, 'PFO_RoleExplicit') ;
INSERT INTO pfo_role_class (class_id, class_name) VALUES (2, 'PFO_RoleAnonymous') ;
INSERT INTO pfo_role_class (class_id, class_name) VALUES (3, 'PFO_RoleLoggedIn') ;

INSERT INTO pfo_role (role_id, role_name, role_class, is_public) VALUES (1, 'Anonymous', '2', true) ;
INSERT INTO pfo_role (role_id, role_name, role_class, is_public) VALUES (2, 'LoggedIn', '3', true) ;

