-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Окт 23 2026 г., 06:27
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `mydb`
--

DELIMITER $$
--
-- Процедуры
--
DROP PROCEDURE IF EXISTS `show_animals_in_complex`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `show_animals_in_complex` (IN `c_name` VARCHAR(100))   BEGIN
    SELECT s.species_name, r.room_number
    FROM animal_species s
    JOIN animal_placement ap ON s.id_specie = ap.id_species
    JOIN rooms r ON ap.id_room = r.id_room
    JOIN complexes c ON r.id_complexes = c.id_complexes
    WHERE c.complex_name = c_name;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `animal_placement`
--

DROP TABLE IF EXISTS `animal_placement`;
CREATE TABLE `animal_placement` (
  `id_species` int(11) NOT NULL,
  `id_room` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `animal_placement`
--

INSERT INTO `animal_placement` (`id_species`, `id_room`) VALUES
(1, 2),
(4, 1),
(5, 1),
(7, 2),
(8, 3),
(9, 5),
(10, 6),
(11, 4);

--
-- Триггеры `animal_placement`
--
DROP TRIGGER IF EXISTS `check_animal_compatibility`;
DELIMITER $$
CREATE TRIGGER `check_animal_compatibility` BEFORE INSERT ON `animal_placement` FOR EACH ROW BEGIN
    DECLARE animal_family VARCHAR(100);
    DECLARE predators INT;
    DECLARE others INT;

    SELECT family INTO animal_family
    FROM animal_species WHERE id_specie = NEW.id_species;

    SELECT
        COALESCE(SUM(a.family IN ('Кошачьи','Псовые','Медвежьи')), 0),
        COALESCE(SUM(a.family NOT IN ('Кошачьи','Псовые','Медвежьи')), 0)
    INTO predators, others
    FROM animal_placement ap
    JOIN animal_species a ON ap.id_species = a.id_specie
    WHERE ap.id_room = NEW.id_room;

    IF animal_family IN ('Кошачьи','Псовые','Медвежьи') AND others > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Хищников нельзя размещать с травоядными!';
    END IF;

    IF animal_family NOT IN ('Кошачьи','Псовые','Медвежьи') AND predators > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Травоядных нельзя размещать с хищниками!';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `check_room_capacity_insert`;
DELIMITER $$
CREATE TRIGGER `check_room_capacity_insert` BEFORE INSERT ON `animal_placement` FOR EACH ROW BEGIN
    DECLARE current_count INT;
    
    -- Считаем, сколько животных уже находится в этой конкретной комнате/вольере
    SELECT COUNT(*) INTO current_count
    FROM animal_placement
    WHERE id_room = NEW.id_room;
    
    -- Если лимит достигнут, отменяем операцию
    IF current_count >= 3 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'В комнате слишком много животных (максимум 3)!';
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `check_room_capacity_update`;
DELIMITER $$
CREATE TRIGGER `check_room_capacity_update` BEFORE UPDATE ON `animal_placement` FOR EACH ROW BEGIN
    DECLARE animal_family VARCHAR(100);
    DECLARE current_count INT;
    DECLARE predators INT;
    DECLARE others INT;

    IF NEW.id_room <> OLD.id_room THEN
        SELECT COUNT(*) INTO current_count
        FROM animal_placement WHERE id_room = NEW.id_room;

        IF current_count >= 3 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'В комнате слишком много животных (максимум 3)!';
        END IF;

        SELECT family INTO animal_family
        FROM animal_species WHERE id_specie = NEW.id_species;

        SELECT
            COALESCE(SUM(a.family IN ('Кошачьи','Псовые','Медвежьи')), 0),
            COALESCE(SUM(a.family NOT IN ('Кошачьи','Псовые','Медвежьи')), 0)
        INTO predators, others
        FROM animal_placement ap
        JOIN animal_species a ON ap.id_species = a.id_specie
        WHERE ap.id_room = NEW.id_room;

        IF animal_family IN ('Кошачьи','Псовые','Медвежьи') AND others > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Хищников нельзя размещать с травоядными!';
        END IF;

        IF animal_family NOT IN ('Кошачьи','Псовые','Медвежьи') AND predators > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Травоядных нельзя размещать с хищниками!';
        END IF;
    END IF;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `tr_count_animals_delete`;
DELIMITER $$
CREATE TRIGGER `tr_count_animals_delete` AFTER DELETE ON `animal_placement` FOR EACH ROW BEGIN
    UPDATE rooms SET total_animals = total_animals - 1 WHERE id_room = OLD.id_room;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `tr_count_animals_insert`;
DELIMITER $$
CREATE TRIGGER `tr_count_animals_insert` AFTER INSERT ON `animal_placement` FOR EACH ROW BEGIN
    UPDATE rooms SET total_animals = total_animals + 1 WHERE id_room = NEW.id_room;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `tr_count_animals_update`;
DELIMITER $$
CREATE TRIGGER `tr_count_animals_update` AFTER UPDATE ON `animal_placement` FOR EACH ROW IF OLD.id_room <> NEW.id_room THEN
    UPDATE rooms SET total_animals = total_animals - 1 WHERE id_room = OLD.id_room;
    UPDATE rooms SET total_animals = total_animals + 1 WHERE id_room = NEW.id_room;
END IF
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `animal_species`
--

DROP TABLE IF EXISTS `animal_species`;
CREATE TABLE `animal_species` (
  `id_specie` int(11) NOT NULL,
  `family` varchar(45) NOT NULL,
  `continent` varchar(45) NOT NULL,
  `daily_food` decimal(6,2) NOT NULL,
  `species_name` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `animal_species`
--

INSERT INTO `animal_species` (`id_specie`, `family`, `continent`, `daily_food`, `species_name`) VALUES
(1, 'Жирафовые', 'Африка', 25.00, 'Жираф'),
(3, 'Псовые', 'Евразия', 3.52, 'Волк'),
(4, 'Кошачьи', 'Африка', 7.00, 'Лев'),
(5, 'Кошачьи', 'Азия', 8.50, 'Тигр'),
(6, 'Слоновые', 'Африка', 150.00, 'Слон'),
(7, 'Лошадиные', 'Африка', 10.00, 'Зебра'),
(8, 'Медвежьи', 'Евразия', 12.00, 'Медведь'),
(9, 'Пингвиновые', 'Антарктида', 1.20, 'Пингвин'),
(10, 'Кенгуровые', 'Австралия', 3.00, 'Кенгуру'),
(11, 'Хамелеоны', 'Мадагаскар', 0.05, 'Хамелеон'),
(12, 'Коалы', 'Австралия', 1.00, 'Коала'),
(13, 'Коалы', 'Австралия', 1.00, 'Коала');

--
-- Триггеры `animal_species`
--
DROP TRIGGER IF EXISTS `validate_animal_species`;
DELIMITER $$
CREATE TRIGGER `validate_animal_species` BEFORE INSERT ON `animal_species` FOR EACH ROW IF NEW.daily_food <= 0 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Количество корма должно быть положительным!';
END IF
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `validate_animal_species_update`;
DELIMITER $$
CREATE TRIGGER `validate_animal_species_update` BEFORE UPDATE ON `animal_species` FOR EACH ROW BEGIN
    IF NEW.daily_food <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Количество корма должно быть положительным!';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `complexes`
--

DROP TABLE IF EXISTS `complexes`;
CREATE TABLE `complexes` (
  `id_complexes` int(11) NOT NULL,
  `complex_name` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `complexes`
--

INSERT INTO `complexes` (`id_complexes`, `complex_name`) VALUES
(1, 'Африканская саванна'),
(2, 'Леса Евразии'),
(3, 'Остров Мадагаскар'),
(4, 'Полярный полюс'),
(5, 'Австралийский парк');

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id_room` int(11) NOT NULL,
  `room_number` varchar(45) NOT NULL,
  `has_water` tinyint(1) NOT NULL,
  `has_heating` tinyint(1) NOT NULL,
  `id_complexes` int(11) NOT NULL,
  `total_animals` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id_room`, `room_number`, `has_water`, `has_heating`, `id_complexes`, `total_animals`) VALUES
(1, '101-A', 1, 1, 1, 2),
(2, '102-B', 0, 1, 1, 2),
(3, '201-E', 1, 0, 2, 1),
(4, '305-M', 1, 1, 3, 1),
(5, '400-P', 1, 0, 4, 1),
(6, '501-A', 0, 1, 5, 1);

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `v_african_species`
-- (См. Ниже фактическое представление)
--
DROP VIEW IF EXISTS `v_african_species`;
CREATE TABLE `v_african_species` (
`id_specie` int(11)
,`species_name` varchar(45)
,`family` varchar(45)
,`daily_food` decimal(6,2)
);

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `v_continent_food_stats`
-- (См. Ниже фактическое представление)
--
DROP VIEW IF EXISTS `v_continent_food_stats`;
CREATE TABLE `v_continent_food_stats` (
`continent` varchar(45)
,`species_count` bigint(21)
,`total_food` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `v_eurasian_predators`
-- (См. Ниже фактическое представление)
--
DROP VIEW IF EXISTS `v_eurasian_predators`;
CREATE TABLE `v_eurasian_predators` (
`id_specie` int(11)
,`species_name` varchar(45)
,`family` varchar(45)
,`daily_food` decimal(6,2)
);

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `v_expensive_animals`
-- (См. Ниже фактическое представление)
--
DROP VIEW IF EXISTS `v_expensive_animals`;
CREATE TABLE `v_expensive_animals` (
`id_specie` int(11)
,`species_name` varchar(45)
,`daily_food` decimal(6,2)
);

-- --------------------------------------------------------

--
-- Дублирующая структура для представления `v_full_placement_report`
-- (См. Ниже фактическое представление)
--
DROP VIEW IF EXISTS `v_full_placement_report`;
CREATE TABLE `v_full_placement_report` (
`Животное` varchar(45)
,`Комната` varchar(45)
,`Комплекс` varchar(45)
);

-- --------------------------------------------------------

--
-- Структура для представления `v_african_species`
--
DROP TABLE IF EXISTS `v_african_species`;

DROP VIEW IF EXISTS `v_african_species`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_african_species`  AS SELECT `animal_species`.`id_specie` AS `id_specie`, `animal_species`.`species_name` AS `species_name`, `animal_species`.`family` AS `family`, `animal_species`.`daily_food` AS `daily_food` FROM `animal_species` WHERE `animal_species`.`continent` = 'Африка' ;

-- --------------------------------------------------------

--
-- Структура для представления `v_continent_food_stats`
--
DROP TABLE IF EXISTS `v_continent_food_stats`;

DROP VIEW IF EXISTS `v_continent_food_stats`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_continent_food_stats`  AS SELECT `animal_species`.`continent` AS `continent`, count(0) AS `species_count`, sum(`animal_species`.`daily_food`) AS `total_food` FROM `animal_species` GROUP BY `animal_species`.`continent` ;

-- --------------------------------------------------------

--
-- Структура для представления `v_eurasian_predators`
--
DROP TABLE IF EXISTS `v_eurasian_predators`;

DROP VIEW IF EXISTS `v_eurasian_predators`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_eurasian_predators`  AS SELECT `animal_species`.`id_specie` AS `id_specie`, `animal_species`.`species_name` AS `species_name`, `animal_species`.`family` AS `family`, `animal_species`.`daily_food` AS `daily_food` FROM `animal_species` WHERE `animal_species`.`continent` = 'Евразия' AND `animal_species`.`family` in ('Псовые','Медвежьи','Кошачьи') ;

-- --------------------------------------------------------

--
-- Структура для представления `v_expensive_animals`
--
DROP TABLE IF EXISTS `v_expensive_animals`;

DROP VIEW IF EXISTS `v_expensive_animals`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_expensive_animals`  AS SELECT `animal_species`.`id_specie` AS `id_specie`, `animal_species`.`species_name` AS `species_name`, `animal_species`.`daily_food` AS `daily_food` FROM `animal_species` WHERE `animal_species`.`daily_food` > 20.0 ;

-- --------------------------------------------------------

--
-- Структура для представления `v_full_placement_report`
--
DROP TABLE IF EXISTS `v_full_placement_report`;

DROP VIEW IF EXISTS `v_full_placement_report`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_full_placement_report`  AS SELECT `s`.`species_name` AS `Животное`, `r`.`room_number` AS `Комната`, `c`.`complex_name` AS `Комплекс` FROM (((`animal_placement` `ap` join `animal_species` `s` on(`ap`.`id_species` = `s`.`id_specie`)) join `rooms` `r` on(`ap`.`id_room` = `r`.`id_room`)) join `complexes` `c` on(`r`.`id_complexes` = `c`.`id_complexes`)) ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `animal_placement`
--
ALTER TABLE `animal_placement`
  ADD PRIMARY KEY (`id_species`,`id_room`),
  ADD KEY `fk_enclosure_has_animal_enclosure_idx` (`id_room`);

--
-- Индексы таблицы `animal_species`
--
ALTER TABLE `animal_species`
  ADD PRIMARY KEY (`id_specie`),
  ADD UNIQUE KEY `animal_species_UNIQUE` (`id_specie`);

--
-- Индексы таблицы `complexes`
--
ALTER TABLE `complexes`
  ADD PRIMARY KEY (`id_complexes`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id_room`),
  ADD UNIQUE KEY `id_room_UNIQUE` (`id_room`),
  ADD KEY `fk_rooms_complexes1_idx` (`id_complexes`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `animal_species`
--
ALTER TABLE `animal_species`
  MODIFY `id_specie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `complexes`
--
ALTER TABLE `complexes`
  MODIFY `id_complexes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `animal_placement`
--
ALTER TABLE `animal_placement`
  ADD CONSTRAINT `fk_enclosure_has_animal_animal1` FOREIGN KEY (`id_species`) REFERENCES `animal_species` (`id_specie`),
  ADD CONSTRAINT `fk_enclosure_has_animal_enclosure` FOREIGN KEY (`id_room`) REFERENCES `rooms` (`id_room`);

--
-- Ограничения внешнего ключа таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_rooms_complexes1` FOREIGN KEY (`id_complexes`) REFERENCES `complexes` (`id_complexes`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
