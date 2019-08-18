<?php
require_once("globals.php");

$db_connection = mysqli_connect($db_host, $db_user, $db_password, $db_address);

// Creating tables
$db_connection->query(
    "CREATE TABLE `games` (
        `id` mediumint(8) UNSIGNED NOT NULL,
        `players` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
        `turn` tinyint(4) UNSIGNED NOT NULL DEFAULT 0,
        `future_cards` tinytext DEFAULT NULL,
        `last_update_ts` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `locked` tinyint(1) NOT NULL DEFAULT 0,
        `turn_length` INT NOT NULL DEFAULT '600', 
        `token` CHAR(10) NULL DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$db_connection->query(
    "CREATE TABLE `actions` (
        `id` int(10) UNSIGNED NOT NULL,
        `player_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
        `currency` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
        `level` tinyint(3) UNSIGNED DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$db_connection->query(
    "CREATE TABLE `logs` (
        `id` int(10) UNSIGNED NOT NULL,
        `game_id` mediumint(8) UNSIGNED NOT NULL,
        `turn` tinyint(3) UNSIGNED NOT NULL,
        `type` text DEFAULT NULL,
        `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
        `ts` timestamp NOT NULL DEFAULT current_timestamp()
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$db_connection->query(
    "CREATE TABLE `players` (
        `internal_id` int(11) NOT NULL,
        `external_id` char(20) NOT NULL,
        `name` text NOT NULL,
        `game_id` mediumint(8) UNSIGNED NOT NULL,
        `vp` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
        `resources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
        `cards` tinytext DEFAULT NULL,
        `card_picked` smallint(5) UNSIGNED DEFAULT NULL,
        `commit` tinyint(1) NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$db_connection->query(
    "CREATE TABLE `currencies` (
        `id` tinyint(3) UNSIGNED NOT NULL,
        `name` text NOT NULL,
        `industry_name` text NOT NULL,
        `victory_condition` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = 1 4th level. 1 = 1st level. 2 = money.',
        `vp_start_cost` smallint(5) UNSIGNED DEFAULT NULL,
        `vp_cost_mult` tinyint(3) UNSIGNED DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$db_connection->query(
    "CREATE TABLE `producers` (
        `id` tinyint(3) UNSIGNED NOT NULL,
        `currency_id` tinyint(3) UNSIGNED NOT NULL,
        `name` text NOT NULL DEFAULT '',
        `pic_name` text NOT NULL,
        `level` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
        `derivative` tinyint(1) NOT NULL DEFAULT 1,
        `production_rate` smallint(5) UNSIGNED DEFAULT 0,
        `base_cost` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
        `cost_mult` float UNSIGNED NOT NULL DEFAULT 1,
        `money_cost_mult` float UNSIGNED NOT NULL DEFAULT 1,
        `prev_level_cost` smallint(5) UNSIGNED DEFAULT NULL,
        `production_increase` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
        `uses_zero_level` tinyint(1) NOT NULL DEFAULT 0
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$db_connection->query(
    "CREATE TABLE `cards` (
        `id` smallint(5) UNSIGNED NOT NULL,
        `onstart` tinyint(1) NOT NULL DEFAULT 1,
        `type` smallint(5) UNSIGNED NOT NULL,
        `name` text NOT NULL,
        `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


// Dumping data
$db_connection->query(
    "INSERT INTO `currencies` (`id`, `name`, `industry_name`, `victory_condition`, `vp_start_cost`, `vp_cost_mult`) VALUES
    (1, 'meat', 'swarmsim', 0, NULL, NULL),
    (2, 'potato', 'adcom', 0, NULL, NULL),
    (3, 'derivative', 'derivativeclicker', 1, 30, 2),
    (4, 'antimatter', 'antidim', 1, 30, 2),
    (5, 'dollar', 'adcap', 2, 80, 2),
    (6, 'cookie', 'cookieclicker', 2, 80, 2);");

$db_connection->query(
    "INSERT INTO `producers` (`id`, `currency_id`, `name`, `pic_name`, `level`, `derivative`, `production_rate`, `base_cost`, `cost_mult`, `money_cost_mult`, `prev_level_cost`, `production_increase`, `uses_zero_level`) VALUES
    (1, 1, 'Drone', 'drone', 1, 0, 2, 1, 1, 1, NULL, 0, 1),
    (2, 1, 'Queen', 'queen', 2, 1, 2, 2, 1, 2, 3, 0, 1),
    (3, 1, 'Nest', 'nest', 3, 1, 2, 4, 1, 5, 3, 0, 1),
    (4, 1, 'Greater Queen', 'greater_queen', 4, 1, 2, 8, 1, 10, 3, 0, 1),
    (5, 1, 'Larvae', 'larvae', 0, 0, NULL, 1, 1.2, 1, NULL, 0, 0),
    (6, 2, 'Comrade', 'comrade', 0, 0, NULL, 1, 1.25, 1, NULL, 0, 0),
    (7, 2, 'Farmer', 'farmer', 1, 0, 4, 2, 1, 1, NULL, 0, 1),
    (8, 2, 'Commune', 'commune', 2, 1, 3, 5, 1, 2, 4, 0, 1),
    (9, 2, 'Freight', 'freight', 3, 1, 2, 20, 1, 5, 3, 0, 1),
    (10, 2, 'Plantation', 'plantation', 4, 1, 1, 60, 1, 10, 2, 0, 1),
    (11, 3, '1st Derivative', 'first', 0, 0, 1, 1, 1.03, 1, NULL, 0, 0),
    (12, 3, '2nd Derivative', 'second', 1, 1, 1, 8, 1.03, 1.5, NULL, 0, 0),
    (13, 3, '3rd Derivative', 'third', 2, 1, 2, 20, 1.03, 2.5, NULL, 0, 0),
    (14, 3, '4th Derivative', 'fourth', 3, 1, 3, 40, 1.03, 5, NULL, 0, 0),
    (15, 3, '5th Derivative', 'fifth', 4, 1, 4, 80, 1.03, 10, NULL, 0, 0),
    (16, 4, '1st Antimatter Dimension', 'first', 0, 0, 1, 2, 5, 1, NULL, 1, 0),
    (17, 4, '2nd Antimatter Dimension', 'second', 1, 1, 1, 8, 5, 1.5, NULL, 1, 0),
    (18, 4, '3rd Antimatter Dimension', 'third', 2, 1, 1, 35, 5, 2.5, NULL, 1, 0),
    (19, 4, '4th Antimatter Dimension', 'fourth', 3, 1, 1, 100, 5, 5, NULL, 1, 0),
    (20, 4, '5th Antimatter Dimension', 'fifth', 4, 1, 1, 350, 5, 10, NULL, 1, 0),
    (21, 5, 'Lemonade Stand', 'lemonade', 0, 0, 1, 1, 1.07, 1, NULL, 0, 0),
    (22, 5, 'Newspaper Delivery', 'newspaper', 1, 0, 4, 5, 1.07, 1.5, NULL, 0, 0),
    (23, 5, 'Car Wash', 'car', 2, 0, 12, 16, 1.07, 2, NULL, 0, 0),
    (24, 5, 'Pizza Delivery', 'pizza', 3, 0, 27, 40, 1.07, 3, NULL, 0, 0),
    (25, 5, 'Donut Shop', 'donut', 4, 0, 60, 100, 1.07, 5, NULL, 0, 0),
    (26, 6, 'Cursor', 'cursor', 0, 0, 2, 1, 1.15, 1, NULL, 0, 0),
    (27, 6, 'Grandma', 'grandma', 1, 0, 8, 10, 1.15, 1.5, NULL, 0, 0),
    (28, 6, 'Farm', 'farm', 2, 0, 25, 40, 1.15, 2, NULL, 0, 0),
    (29, 6, 'Mine', 'mine', 3, 0, 40, 80, 1.15, 3, NULL, 0, 0),
    (30, 6, 'Factory', 'factory', 4, 0, 70, 160, 1.15, 5, NULL, 0, 0);");

$db_connection->query(
    "INSERT INTO `cards` (`id`, `onstart`, `type`, `name`, `data`) VALUES
    (1, 1, 0, 'Locusts', '{ \"industry\": 1 }'),
    (2, 1, 0, 'Revolution', '{ \"industry\": 2 }'),
    (3, 1, 0, 'Integration', '{ \"industry\": 3 }'),
    (4, 1, 0, 'Explosion', '{ \"industry\": 4 }'),
    (5, 1, 0, 'Bankruptcy', '{ \"industry\": 5 }'),
    (6, 1, 0, 'Cookie War', '{ \"industry\": 6 }'),
    (7, 1, 1, 'Swarm Growth', '{ \"industry\": 1 }'),
    (8, 1, 1, 'The Party', '{ \"industry\": 2 }'),
    (9, 1, 2, 'Hunger', '{ \"industry\" : 1 }'),
    (10, 1, 2, 'Famine', '{ \"industry\" : 2 }'),
    (11, 1, 2, 'Zero Results', '{ \"industry\" : 3 }'),
    (12, 1, 2, 'Collapse', '{ \"industry\" : 4 }'),
    (13, 1, 2, 'Devaluation', '{ \"industry\" : 5 }'),
    (14, 1, 2, 'Cookie Eater', '{ \"industry\" : 6 }'),
    (15, 1, 3, 'Great Hunt', '{ \"industry\" : 1 }'),
    (16, 1, 3, 'Good Harvest', '{ \"industry\" : 2 }'),
    (17, 1, 3, 'Philosophy', '{ \"industry\" : 3 }'),
    (18, 1, 3, 'CERN', '{ \"industry\" : 4 }'),
    (19, 1, 3, 'Inheritance', '{ \"industry\" : 5 }'),
    (20, 1, 3, 'Wake and Bake', '{ \"industry\" : 6 }'),
    (21, 1, 4, 'Deposit', NULL),
    (22, 1, 5, 'Expansion', '{ \"industry\" : 5 }'),
    (23, 1, 5, 'Banana Taste', '{ \"industry\" : 6 }'),
    (24, 1, 6, 'Breakthrough', '{ \"industry\" : 3 }'),
    (25, 1, 6, 'Replicanti', '{ \"industry\" : 4 }'),
    (26, 0, 7, 'Scrooge', NULL),
    (27, 0, 8, 'Meat Tower', '{ \"industry\" : 1 }'),
    (28, 0, 8, 'Socialism', '{ \"industry\" : 2 }'),
    (29, 0, 8, 'Mathematics', '{ \"industry\" : 3 }'),
    (30, 0, 8, 'Anti-World', '{ \"industry\" : 4 }'),
    (31, 0, 8, 'Filthy Rich', '{ \"industry\" : 5 }'),
    (32, 0, 8, 'Cookie Empire', '{ \"industry\" : 6 }'),
    (33, 0, 9, 'Evolution', '{ \"industry\" : 1 }'),
    (34, 0, 9, 'Rank Up', '{ \"industry\" : 2 }'),
    (35, 0, 9, 'Tiered Reset', '{ \"industry\" : 3 }'),
    (36, 0, 9, 'Dimboost', '{ \"industry\" : 4 }'),
    (37, 0, 9, 'Angelic Reset', '{ \"industry\" : 5 }'),
    (38, 0, 9, 'Legacy', '{ \"industry\" : 6 }'),
    (39, 0, 10, 'Greater Queens', '{ \"industry\" : 1 }'),
    (40, 0, 10, 'Irrigation', '{ \"industry\" : 2 }'),
    (41, 0, 10, 'Many Proofs', '{ \"industry\" : 3 }'),
    (42, 0, 10, 'Infinity Break', '{ \"industry\" : 4 }'),
    (43, 0, 10, 'Moon Colony', '{ \"industry\" : 5 }'),
    (44, 0, 10, 'Golden Cookie', '{ \"industry\" : 6 }'),
    (45, 0, 11, 'Fresh Start', NULL),
    (46, 0, 12, 'Liquidation', NULL),
    (47, 0, 13, 'Cold Winter', '{ \"industry\" : 1 }'),
    (48, 0, 13, 'Capitalism', '{ \"industry\" : 2 }'),
    (49, 0, 13, 'Dark Ages', '{ \"industry\" : 3 }'),
    (50, 0, 13, 'Matter Reign', '{ \"industry\" : 4 }'),
    (51, 0, 13, 'Holidays', '{ \"industry\" : 5 }'),
    (52, 0, 13, 'Embargo', '{ \"industry\" : 6 }');");


// Creating indexes
$db_connection->query(
    "ALTER TABLE `games`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `OPEN_GAMES` (`turn`,`last_update_ts`);");

$db_connection->query(
    "ALTER TABLE `actions`
    ADD PRIMARY KEY (`id`),
    ADD KEY `PLAYER` (`player_id`);");

$db_connection->query(
    "ALTER TABLE `logs`
    ADD PRIMARY KEY (`id`),
    ADD KEY `LOGRABBER` (`game_id`,`type`(30)) USING BTREE;");

$db_connection->query(
    "ALTER TABLE `players`
    ADD PRIMARY KEY (`internal_id`),
    ADD UNIQUE KEY `EXTID` (`external_id`);");

$db_connection->query(
    "ALTER TABLE `currencies`
    ADD PRIMARY KEY (`id`);");

$db_connection->query(
    "ALTER TABLE `producers`
    ADD PRIMARY KEY (`id`),
    ADD KEY `CURRENCY` (`currency_id`);");

$db_connection->query(
    "ALTER TABLE `cards`
    ADD PRIMARY KEY (`id`);");


// Setting auto-increments
$db_connection->query(
    "ALTER TABLE `games`
    MODIFY `id` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;");

$db_connection->query(
    "ALTER TABLE `actions`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;");

$db_connection->query(
    "ALTER TABLE `logs`
    MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;");

$db_connection->query(
    "ALTER TABLE `players`
    MODIFY `internal_id` int(11) NOT NULL AUTO_INCREMENT;");

$db_connection->query(
    "ALTER TABLE `currencies`
    MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;");

$db_connection->query(
    "ALTER TABLE `producers`
    MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;");

$db_connection->query(
    "ALTER TABLE `cards`
    MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;");

$db_connection->close();
?>