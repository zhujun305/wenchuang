
/*Table structure for table `sys_adminuser` */

DROP TABLE IF EXISTS `sys_adminuser`;

CREATE TABLE `sys_adminuser` (
  `id` int(10) NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` char(50) DEFAULT '' COMMENT '角色名称',
  `pwd` char(50) DEFAULT '' COMMENT '用户密码',
  `truename` char(50) DEFAULT '' COMMENT '姓名',
  `mobile` char(15) DEFAULT '' COMMENT '移动电话',
  `email` varchar(30) DEFAULT '' COMMENT '电子邮箱',
  `qq` varchar(20) DEFAULT '' COMMENT 'qq',
  `winxin` varchar(15) DEFAULT '' COMMENT '微信',
  `roseid` int(5) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `logintime` int(10) NOT NULL DEFAULT '0' COMMENT '登录时间',
  `loginip` char(20) NOT NULL DEFAULT '' COMMENT '登录IP',
  `input_time` int(10) DEFAULT '0' COMMENT '录入时间',
  `input_uid` int(10) DEFAULT '0' COMMENT '录入人',
  `is_lock` tinyint(1) DEFAULT '1' COMMENT '1正常2锁定',
  `is_del` tinyint(1) DEFAULT '1' COMMENT '1正常2删除',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统用户表';

/*Data for the table `sys_adminuser` */

insert  into `sys_adminuser`(`id`,`name`,`pwd`,`truename`,`mobile`,`email`,`qq`,`winxin`,`roseid`,`logintime`,`loginip`,`input_time`,`input_uid`,`is_lock`,`is_del`) values 
(1,'superadmin','e10adc3949ba59abbe56e057f20f883e','超级管理员',NULL,'',NULL,NULL,-1,0,'',0,0,1,1),
(2,'zhujun','e10adc3949ba59abbe56e057f20f883e','晴天','','','','',-1,0,'',1488961495,0,1,1);

/*Table structure for table `sys_power` */

DROP TABLE IF EXISTS `sys_power`;

CREATE TABLE `sys_power` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `pid` int(11) NOT NULL COMMENT '权限上级ID',
  `moduel` char(50) NOT NULL COMMENT '模块',
  `action` char(50) NOT NULL COMMENT '控制器',
  `method` char(50) NOT NULL COMMENT '方法名',
  `paramter` char(200) NOT NULL COMMENT '参数',
  `actionName` char(50) DEFAULT NULL COMMENT '控制器描述',
  `methodName` char(50) DEFAULT NULL COMMENT '方法描述',
  `type` int(4) NOT NULL DEFAULT '1' COMMENT '权限类型 1系统模块 2控制器 3方法',
  `status` int(4) NOT NULL DEFAULT '1' COMMENT '权限状态 1正常 2锁定',
  `isShow` int(4) NOT NULL DEFAULT '2' COMMENT '是否显示 1作为菜单显示 2不作为菜单不显示',
  `sort` int(4) DEFAULT '0' COMMENT '排序',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统权限表';

/*Data for the table `sys_power` */

insert  into `sys_power`(`id`,`pid`,`moduel`,`action`,`method`,`paramter`,`actionName`,`methodName`,`type`,`status`,`isShow`,`sort`) values 
(1,0,'系统管理','','','','','',1,1,1,0),
(2,0,'用户管理','','','','','',1,1,1,0),
(3,0,'素材管理','','','','','',1,1,1,0),
(4,0,'众包管理','','','','','',1,1,1,0),
(5,0,'众筹管理','','','','','',1,1,1,0),
(6,0,'产品管理','','','','','',1,1,1,0),
(100,1,'','sysrose','','','角色管理','',2,1,2,NULL),
(101,100,'','sysrose','browse','','角色管理','角色管理',3,1,1,NULL),
(102,100,'','sysrose','create','','角色管理','新增',3,1,2,NULL),
(103,100,'','sysrose','edit','','角色管理','编辑',3,1,2,NULL),
(104,100,'','sysrose','delete','','角色管理','删除',3,1,2,NULL),
(110,1,'','sysadminuser','','','系统用户管理','',2,1,2,NULL),
(111,110,'','sysadminuser','browse','','系统用户管理','系统用户管理',3,1,1,NULL),
(112,110,'','sysadminuser','create','','系统用户管理','新增',3,1,2,NULL),
(113,110,'','sysadminuser','edit','','系统用户管理','编辑',3,1,2,NULL),
(114,110,'','sysadminuser','delete','','系统用户管理','删除',3,1,2,NULL),
(120,1,'','syspower','','','系统权限管理','',2,1,2,NULL),
(121,120,'','syspower','browse','','系统权限管理','系统权限管理',3,1,1,NULL),
(122,120,'','syspower','create','','系统权限管理','新增',3,1,2,NULL),
(123,120,'','syspower','edit','','系统权限管理','编辑',3,1,2,NULL),
(124,120,'','syspower','delete','','系统权限管理','删除',3,1,2,NULL);

/*Table structure for table `sys_rose` */

DROP TABLE IF EXISTS `sys_rose`;

CREATE TABLE `sys_rose` (
  `id` int(4) NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` varchar(50) DEFAULT NULL COMMENT '角色名称',
  `powerStr` varchar(2000) DEFAULT NULL COMMENT '角色对应权限字符串',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统角色表';

/*Data for the table `sys_rose` */

insert  into `sys_rose`(`id`,`name`,`powerStr`) values 
(1,'管理员','');

/*Table structure for table `sys_rosepower` */

DROP TABLE IF EXISTS `sys_rosepower`;

CREATE TABLE `sys_rosepower` (
  `id` int(4) NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `roseid` int(4) NOT NULL DEFAULT '0' COMMENT '角色ID',
  `powerid` int(4) NOT NULL DEFAULT '0' COMMENT '权限ID',
  `action` varchar(20) NOT NULL DEFAULT '' COMMENT '控制器',
  `method` varchar(20) NOT NULL DEFAULT '' COMMENT '方法名',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='系统角色权限表';





