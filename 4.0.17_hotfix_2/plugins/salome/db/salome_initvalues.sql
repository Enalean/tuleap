-- 
-- Database: `salome`: initial values
-- 


INSERT INTO `CONFIG` (`CLE`, `id_projet`, `id_personne`, `VALEUR`) VALUES
('cx.trk.grp_id',        0000000100, 0000000000, '100'),
('cx.trk.grp_art_id',    0000000100, 0000000000, '6'),
('cx.trk.env.report_id', 0000000100, 0000000000, '5'),
('cx.trk.sumry.fld_nm',  0000000100, 0000000000, 'summary'),
('cx.trk.detail.fld_nm', 0000000100, 0000000000, 'details'),
('cx.trk.env.fld_nm',    0000000100, 0000000000, 'slm_environment'),
('cx.trk.camp.fld_nm',   0000000100, 0000000000, 'slm_campaign'),
('cx.trk.family.fld_nm', 0000000100, 0000000000, 'slm_family'),
('cx.trk.suite.fld_nm',  0000000100, 0000000000, 'slm_suite'),
('cx.trk.test.fld_nm',   0000000100, 0000000000, 'slm_test'),
('cx.trk.action.fld_nm', 0000000100, 0000000000, 'slm_action'),
('cx.trk.exec.fld_nm',   0000000100, 0000000000, 'slm_execution'),
('cx.trk.dtset.fld_nm',  0000000100, 0000000000, 'slm_dataset');

INSERT INTO `GROUPE` (`id_groupe`, `PROJET_VOICE_TESTING_id_projet`, `nom_groupe`, `desc_groupe`, `permission`) VALUES
(1, 100, '3', 'Project Members', 0000000182),
(2, 100, '4', 'Project Administrators', 0000000254);

INSERT INTO `PROJET_VOICE_TESTING` (`id_projet`, `nom_projet`, `description_projet`, `date_creation_projet`, `verrou_projet`) VALUES
(100, 'Template', 'The default Codendi template', '2008-07-07', NULL);


-- Original Salome Init

INSERT INTO `CONFIG` (`CLE`, `id_projet`, `id_personne`, `VALEUR`) VALUES ('Locale', 0000000000, 0000000000, 'en');
INSERT INTO `CONFIG` (`CLE`, `id_projet`, `id_personne`, `VALEUR`) VALUES ('LocalesList', 0000000000, 0000000000, 'en,fr');

-- INSERT INTO `PERSONNE` (`id_personne`, `login_personne`, `nom_personne`, `prenom_personne`, `desc_personne`, `email_personne`, `tel_personne`, `date_creation_personne`, `heure_creation_personne`, `mot_de_passe`) VALUES (1, 'AdminSalome', 'Administrateur', '', 'Administrateur de Salome', 'adminsalome@francetelecom.com', '', '2003-05-07', '01:00:00', '21232f297a57a5a743894a0e4a801fc3');
