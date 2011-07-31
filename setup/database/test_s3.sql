SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `s3`
--

-- --------------------------------------------------------

--
-- Table structure for table `acl`
--

DROP TABLE IF EXISTS `acl`;
CREATE TABLE `acl` (
  `id` tinyint(4) NOT NULL auto_increment COMMENT 'acl identifier',
  `type` varchar(12) collate latin1_general_ci NOT NULL COMMENT 'acl type',
  PRIMARY KEY  (`id`),
  KEY `type` (`type`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Access Control List' AUTO_INCREMENT=6 ;

--
-- Dumping data for table `acl`
--

INSERT INTO `acl` VALUES(1, 'FULL_CONTROL');
INSERT INTO `acl` VALUES(2, 'READ');
INSERT INTO `acl` VALUES(3, 'WRITE');
INSERT INTO `acl` VALUES(4, 'READ_ACP');
INSERT INTO `acl` VALUES(5, 'WRITE_ACP');

-- --------------------------------------------------------

--
-- Table structure for table `bucket`
--

DROP TABLE IF EXISTS `bucket`;
CREATE TABLE `bucket` (
  `id` char(64) collate latin1_general_ci NOT NULL,
  `name` varchar(256) collate latin1_general_ci NOT NULL,
  `ownerid` char(64) collate latin1_general_ci NOT NULL,
  `location` char(2) collate latin1_general_ci default NULL,
  `creation_date` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `ownerid` (`ownerid`),
  KEY `location` (`location`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Bucket';

--
-- Dumping data for table `bucket`
--

-- --------------------------------------------------------

--
-- Table structure for table `group`
--

DROP TABLE IF EXISTS `group`;
CREATE TABLE `group` (
  `id` tinyint(4) NOT NULL COMMENT 'Group ID',
  `type` varchar(20) character set utf8 NOT NULL COMMENT 'Group permission',
  PRIMARY KEY  (`id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `group`
--

INSERT INTO `group` VALUES(1, 'AllUsers');
INSERT INTO `group` VALUES(2, 'AuthenticatedUsers');

-- --------------------------------------------------------

--
-- Table structure for table `object`
--

DROP TABLE IF EXISTS `object`;
CREATE TABLE `object` (
  `id` char(64) collate latin1_general_ci NOT NULL,
  `key` varchar(512) collate latin1_general_ci NOT NULL,
  `bucketid` char(64) collate latin1_general_ci NOT NULL,
  `ownerid` char(64) collate latin1_general_ci NOT NULL,
  `value` longblob NOT NULL,
  `metadata` varchar(4096) collate latin1_general_ci default NULL,
  `content_type` varchar(32) collate latin1_general_ci NOT NULL,
  `etag` char(32) collate latin1_general_ci NOT NULL,
  `last_updated` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`key`,`bucketid`),
  UNIQUE KEY `id` (`id`),
  KEY `ownerid` (`ownerid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Object';

--
-- Dumping data for table `object`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` char(64) collate latin1_general_ci NOT NULL,
  `display_name` varchar(64) collate latin1_general_ci NOT NULL,
  `email_address` varchar(64) collate latin1_general_ci NOT NULL,
  `access_key` char(20) collate latin1_general_ci NOT NULL,
  `secret_key` char(40) collate latin1_general_ci NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `access_key` (`access_key`),
  KEY `name` (`display_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='Canonical Users';

--
-- Dumping data for table `user`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_bucket`
--

DROP TABLE IF EXISTS `user_bucket`;
CREATE TABLE `user_bucket` (
  `userid` char(64) collate latin1_general_ci default NULL,
  `bucketid` char(64) collate latin1_general_ci NOT NULL,
  `aclid` tinyint(4) NOT NULL,
  `groupid` tinyint(4) default NULL COMMENT 'Group permissions',
  KEY `userid` (`userid`),
  KEY `bucketid` (`bucketid`),
  KEY `aclid` (`aclid`),
  KEY `groupid` (`groupid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='User Bucket Acess Control';

--
-- Dumping data for table `user_bucket`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_object`
--

DROP TABLE IF EXISTS `user_object`;
CREATE TABLE `user_object` (
  `userid` char(64) collate latin1_general_ci default NULL,
  `objectid` char(64) collate latin1_general_ci NOT NULL,
  `aclid` tinyint(4) NOT NULL,
  `groupid` tinyint(4) default NULL COMMENT 'Group permission',
  KEY `objectid` (`objectid`),
  KEY `aclid` (`aclid`),
  KEY `userid` (`userid`),
  KEY `groupid` (`groupid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='User Object Access Control';

