--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: -
--

CREATE OR REPLACE PROCEDURAL LANGUAGE plpgsql;


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: w1_edition_users; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_edition_users (
    edition integer NOT NULL,
    uid integer NOT NULL,
    qualified integer,
    lecturer integer,
    staybegintime integer,
    stayendtime integer,
    isselfcatered integer,
    lastmodification integer
);


--
-- Name: w1_editions; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_editions (
    edition integer NOT NULL,
    name character varying(255),
    begintime integer,
    endtime integer,
    importanthours character varying(50),
    proposaldeadline integer
);


--
-- Name: w1_log; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_log (
    ip inet,
    uid integer,
    "time" integer,
    type character varying(255),
    what integer
);


--
-- Name: w1_options; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_options (
    name character varying(255) NOT NULL,
    description character varying(255),
    value text,
    type character varying(255)
);


--
-- Name: w1_role_permissions; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_role_permissions (
    role character varying(50) NOT NULL,
    action character varying(50) NOT NULL
);


--
-- Name: w1_task_solutions; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_task_solutions (
    wid integer NOT NULL,
    tid integer NOT NULL,
    uid integer NOT NULL,
    submitted integer NOT NULL,
    solution text,
    feedback text,
    status integer,
    grade character varying(255),
    notified integer
);


--
-- Name: w1_tasks; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_tasks (
    wid integer NOT NULL,
    tid integer NOT NULL,
    description text
);


--
-- Name: w1_uploads; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_uploads (
    filename character varying(255) NOT NULL,
    realname character varying(255),
    size integer,
    mimetype character varying(255),
    uploader integer,
    utime integer
);


--
-- Name: w1_user_roles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_user_roles (
    uid integer NOT NULL,
    role character varying(50) NOT NULL
);


--
-- Name: w1_users; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_users (
    uid integer NOT NULL,
    name character varying(255),
    login character varying(255),
    password character varying(255),
    email character varying(255),
    confirm integer,
    registered integer,
    logged integer,
    school character varying(255),
    podanieotutora text,
    tutoruid integer,
    gender character varying(20),
    motivationletter text,
    pesel character varying(30),
    address character varying(255),
    tshirtsize character varying(30),
    telephone character varying(255),
    parenttelephone character varying(255),
    gatherplace character varying(255),
    comments text,
    ordername character varying(255),
    graduationyear integer,
    interests text,
    howdoyouknowus text
);


--
-- Name: w1_users_uid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE w1_users_uid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: w1_users_uid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE w1_users_uid_seq OWNED BY w1_users.uid;


--
-- Name: w1_workshop_subjects; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_workshop_subjects (
    wid integer NOT NULL,
    subject character varying(255) NOT NULL
);


--
-- Name: w1_workshop_subjects_wid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE w1_workshop_subjects_wid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: w1_workshop_subjects_wid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE w1_workshop_subjects_wid_seq OWNED BY w1_workshop_subjects.wid;


--
-- Name: w1_workshop_users; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_workshop_users (
    wid integer NOT NULL,
    uid integer NOT NULL,
    participant integer,
    admincomment text,
    points integer
);


--
-- Name: w1_workshops; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE w1_workshops (
    wid integer NOT NULL,
    title character varying(255),
    description text,
    status integer,
    type integer,
    duration integer,
    link character varying(255),
    tasks_comment text,
    edition integer,
    subjects_order integer
);


--
-- Name: w1_workshops_wid_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE w1_workshops_wid_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: w1_workshops_wid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE w1_workshops_wid_seq OWNED BY w1_workshops.wid;


--
-- Name: uid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE w1_users ALTER COLUMN uid SET DEFAULT nextval('w1_users_uid_seq'::regclass);


--
-- Name: wid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE w1_workshop_subjects ALTER COLUMN wid SET DEFAULT nextval('w1_workshop_subjects_wid_seq'::regclass);


--
-- Name: wid; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE w1_workshops ALTER COLUMN wid SET DEFAULT nextval('w1_workshops_wid_seq'::regclass);


--
-- Name: w1_edition_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_edition_users
    ADD CONSTRAINT w1_edition_users_pkey PRIMARY KEY (edition, uid);


--
-- Name: w1_editions_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_editions
    ADD CONSTRAINT w1_editions_pkey PRIMARY KEY (edition);


--
-- Name: w1_options_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_options
    ADD CONSTRAINT w1_options_pkey PRIMARY KEY (name);


--
-- Name: w1_role_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_role_permissions
    ADD CONSTRAINT w1_role_permissions_pkey PRIMARY KEY (role, action);


--
-- Name: w1_task_solutions_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_task_solutions
    ADD CONSTRAINT w1_task_solutions_pkey PRIMARY KEY (wid, tid, uid, submitted);


--
-- Name: w1_tasks_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_tasks
    ADD CONSTRAINT w1_tasks_pkey PRIMARY KEY (wid, tid);


--
-- Name: w1_uploads_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_uploads
    ADD CONSTRAINT w1_uploads_pkey PRIMARY KEY (filename);


--
-- Name: w1_user_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_user_roles
    ADD CONSTRAINT w1_user_roles_pkey PRIMARY KEY (uid, role);


--
-- Name: w1_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_users
    ADD CONSTRAINT w1_users_pkey PRIMARY KEY (uid);


--
-- Name: w1_workshop_subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_workshop_subjects
    ADD CONSTRAINT w1_workshop_subjects_pkey PRIMARY KEY (wid, subject);


--
-- Name: w1_workshop_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_workshop_users
    ADD CONSTRAINT w1_workshop_users_pkey PRIMARY KEY (wid, uid);

ALTER TABLE w1_workshop_users CLUSTER ON w1_workshop_users_pkey;


--
-- Name: w1_workshops_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY w1_workshops
    ADD CONSTRAINT w1_workshops_pkey PRIMARY KEY (wid);

ALTER TABLE w1_workshops CLUSTER ON w1_workshops_pkey;


--
-- Name: w1_log_time; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX w1_log_time ON w1_log USING btree ("time");

ALTER TABLE w1_log CLUSTER ON w1_log_time;


--
-- Name: w1_user_roles_pkeyt; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX w1_user_roles_pkeyt ON w1_user_roles USING btree (uid, role);

ALTER TABLE w1_user_roles CLUSTER ON w1_user_roles_pkeyt;


--
-- Name: w1_workshops_title; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX w1_workshops_title ON w1_workshops USING btree (title);


--
-- Data for Name: w1_options; Type: TABLE DATA; Schema: public; Owner: - 
--

INSERT INTO w1_options (name, description, value, type) VALUES ('currentEdition', 'current workshop edition', '7', 'int');
INSERT INTO w1_options (name, description, value, type) VALUES ('gmailOAuthEmail', 'gmail account used to send e-mails', 'mwrochna@gmail.com', 'text');
INSERT INTO w1_options (name, description, value, type) VALUES ('homepage', 'main page top content', '', 'richtextarea');
INSERT INTO w1_options (name, description, value, type) VALUES ('motivationLetterWords', 'min of motivation letter words', '150', 'int');
INSERT INTO w1_options (name, description, value, type) VALUES ('gmailOAuthAccessToken', 'accessToken to gmail account <small><a href="fetchGmailOAuthAccessToken">[reauthorize]</a></small>', '', 'readonly');
INSERT INTO w1_options (name, description, value, type) VALUES ('version', 'database version', '50', 'readonly');

--
-- Data for Name: w1_role_permissions; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'editProfile');
INSERT INTO w1_role_permissions (role, action) VALUES ('owner', 'editProfile');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'listPublicWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'listOwnWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'showWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'showWorkshopDetails');
INSERT INTO w1_role_permissions (role, action) VALUES ('owner', 'showWorkshopDetails');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'editWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('owner', 'editWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'changeWorkshopStatus');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'adminUsers');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'listAllWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'editOptions');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'showLog');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'showWorkshopParticipants');
INSERT INTO w1_role_permissions (role, action) VALUES ('owner', 'showWorkshopParticipants');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'editTasks');
INSERT INTO w1_role_permissions (role, action) VALUES ('registered', 'showWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('registered', 'listPublicWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'showCorrelation');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'autoQualifyForWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'viewTutoringApplications');
INSERT INTO w1_role_permissions (role, action) VALUES ('tutor', 'viewTutoringApplications');
INSERT INTO w1_role_permissions (role, action) VALUES ('admin', 'impersonate');
INSERT INTO w1_role_permissions (role, action) VALUES ('registered', 'editTutoringApplication');
INSERT INTO w1_role_permissions (role, action) VALUES ('registered', 'applyAsLecturer');
INSERT INTO w1_role_permissions (role, action) VALUES ('registered', 'applyAsParticipant');
INSERT INTO w1_role_permissions (role, action) VALUES ('user', 'editMotivationLetter');
INSERT INTO w1_role_permissions (role, action) VALUES ('lecturer', 'listPublicWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('lecturer', 'listOwnWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('lecturer', 'showWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('lecturer', 'createWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('lecturer', 'autoQualifyForWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('lecturer', 'signUpForWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('qualified lecturer', 'editTasks');
INSERT INTO w1_role_permissions (role, action) VALUES ('qualified', 'editAdditionalInfo');
INSERT INTO w1_role_permissions (role, action) VALUES ('candidate', 'listPublicWorkshops');
INSERT INTO w1_role_permissions (role, action) VALUES ('candidate', 'showWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('candidate', 'signUpForWorkshop');
INSERT INTO w1_role_permissions (role, action) VALUES ('candidate', 'showQualificationStatus');
INSERT INTO w1_role_permissions (role, action) VALUES ('candidate', 'sendTaskSolution');
INSERT INTO w1_role_permissions (role, action) VALUES ('candidate', 'editMotivationLetter');


--
-- PostgreSQL database dump complete
--

INSERT INTO w1_editions (edition, name, begintime, endtime, importanthours, proposaldeadline) VALUES (7, 'WWW7', 1312819200, 1313654400, '3 9 14 19', 1302854400);
INSERT INTO w1_users (uid, name, login, password, confirm) VALUES (-1, 'root', 'root', 'rootpassword', 0);
INSERT INTO w1_user_roles (uid, role) VALUES (-1, 'admin');
