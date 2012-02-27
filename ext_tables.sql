#
# Table structure for table 'tx_wecstaffdirectory_info'
#
CREATE TABLE tx_wecstaffdirectory_info (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	
	feuser_id int(11) DEFAULT '0' NOT NULL,
	position_title tinytext NOT NULL,
	position_description text NOT NULL,
	department tinytext NOT NULL,
	department_rec tinytext NOT NULL,
	team tinytext NOT NULL,
	start_date int(11) DEFAULT '0' NOT NULL,
	biography text NOT NULL,
	news text NOT NULL,
	photo_main blob NOT NULL,
	photos_etc blob NOT NULL,
	cellphone tinytext NOT NULL,
	social_contact1 varchar(50) DEFAULT '' NOT NULL,
	social_contact2 varchar(50) DEFAULT '' NOT NULL,
	social_contact3 varchar(50) DEFAULT '' NOT NULL,
	misc tinytext NOT NULL,
	display_order tinyint(10) DEFAULT '0' NOT NULL,

	gender int(11) unsigned DEFAULT '0' NOT NULL,
    full_name varchar(100) DEFAULT '' NOT NULL,
	first_name varchar(50) DEFAULT '' NOT NULL,
	last_name varchar(50) DEFAULT '' NOT NULL,
	title varchar(40) DEFAULT '' NOT NULL,
	address tinytext NOT NULL,
	address2 tinytext NOT NULL,
	city varchar(50) DEFAULT '' NOT NULL,
    zone varchar(45) DEFAULT '' NOT NULL,
	zip varchar(10) DEFAULT '' NOT NULL,
	country varchar(40) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	telephone varchar(25) DEFAULT '' NOT NULL,
	fax varchar(25) DEFAULT '' NOT NULL,
	
	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid),
    KEY t3ver_oid (t3ver_oid,t3ver_wsid)
);

CREATE TABLE tx_wecstaffdirectory_department (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	
	department_name varchar(50) DEFAULT '' NOT NULL,
	description text NOT NULL,
	parent_department int(11) DEFAULT '0' NOT NULL,
	image tinytext NOT NULL,

	sys_language_uid int(11) DEFAULT '0' NOT NULL,
	l18n_parent int(11) DEFAULT '0' NOT NULL,
	l18n_diffsource mediumblob NOT NULL,

	t3ver_oid int(11) DEFAULT '0' NOT NULL,
	t3ver_id int(11) DEFAULT '0' NOT NULL,
	t3ver_wsid int(11) DEFAULT '0' NOT NULL,
	t3ver_label varchar(30) DEFAULT '' NOT NULL,
	t3ver_state tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_stage tinyint(4) DEFAULT '0' NOT NULL,
	t3ver_count int(11) DEFAULT '0' NOT NULL,
	t3ver_tstamp int(11) DEFAULT '0' NOT NULL,
	t3_origuid int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid),
	KEY t3ver_oid (t3ver_oid,t3ver_wsid)	
);

CREATE TABLE tx_wecstaffdirectory_department_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,

  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
  tx_wecstaffdirectory_in int(11) DEFAULT '0' NOT NULL,
  gender int(11) unsigned DEFAULT '0' NOT NULL,
  first_name varchar(50) DEFAULT '' NOT NULL,
  last_name varchar(50) DEFAULT '' NOT NULL,
  title varchar(40) DEFAULT '' NOT NULL,
  name varchar(100) DEFAULT '' NOT NULL,
  country varchar(60) DEFAULT '' NOT NULL,
  zip varchar(20) DEFAULT '' NOT NULL,
  email varchar(255) DEFAULT '' NOT NULL,
  telephone varchar(25) DEFAULT '' NOT NULL,
  address2 tinytext NOT NULL,
  zone varchar(45) DEFAULT '' NOT NULL,
);