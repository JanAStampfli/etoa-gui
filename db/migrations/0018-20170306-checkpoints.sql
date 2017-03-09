ALTER TABLE `users` ADD `npc` TINYINT NOT NULL DEFAULT '0';

INSERT INTO `planet_types` (`type_name`, `type_habitable`, `type_comment`, `type_consider`) VALUES
('Kontrollpunkt', '0', 'Placeholder','0');

INSERT INTO `alliance_buildings` (`alliance_building_name`, `alliance_building_longcomment`, `alliance_needed_level`, `alliance_building_last_level`) VALUES
  ('Allianzhafen', 'Von hier können Allianzschiffe gestartet werden',0,'10');

CREATE TABLE IF NOT EXISTS `reward_storage` (
  `storage_user_id` int(10) unsigned NOT NULL,
  `storage_res_metal` decimal(18,6) unsigned,
  `storage_res_crystal` decimal(18,6) unsigned,
  `storage_res_fuel` decimal(18,6) unsigned,
  `storage_res_plastic` decimal(18,6) unsigned,
  `storage_res_food` decimal(18,6) unsigned
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `planets` ADD `planet_bonus_metal` TINYINT DEFAULT '0';
ALTER TABLE `planets` ADD `planet_bonus_crystal` TINYINT DEFAULT '0';
ALTER TABLE `planets` ADD `planet_bonus_plastic` TINYINT DEFAULT '0';
ALTER TABLE `planets` ADD `planet_bonus_fuel` TINYINT DEFAULT '0';
ALTER TABLE `planets` ADD `planet_bonus_food` TINYINT DEFAULT '0';
