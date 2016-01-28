DROP EVENT IF EXISTS timerRessources;
CREATE EVENT timerRessources
ON SCHEDULE EVERY 10 MINUTE
DO CALL calc_resources;

DROP EVENT IF EXISTS timerWeather;
CREATE EVENT timerWeather
ON SCHEDULE EVERY 30 MINUTE
DO CALL calc_weather;

DROP EVENT IF EXISTS timerAngle;
CREATE EVENT timerAngle
ON SCHEDULE EVERY 1 MINUTE
DO CALL calc_angle;


-- PROCEDURE POUR METTRE A JOURS LE TEMPS SUR LES PLANETES
DELIMITER $$
	DROP PROCEDURE IF EXISTS calc_weather$$

-- sunny: 20%, rainy: 20%, cloudy: 20%, windy: 20%, foggy: 10%, snowy: 5%, stormy:5% 
CREATE PROCEDURE calc_weather()
BEGIN
	DECLARE id_planete, rand, done INT DEFAULT 0;
	DECLARE weather_planete, new_weather VARCHAR(255) DEFAULT "sunny";
	DECLARE curs_planete CURSOR FOR 
	SELECT id, weather FROM planete;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	OPEN curs_planete;
	REPEAT FETCH curs_planete INTO id_planete, weather_planete;
		SET rand = (SELECT RAND()) * 100;
		IF rand >= 0 AND rand < 20 THEN
			SET new_weather = "sunny";
		END IF;

		IF rand >= 20 AND rand < 40 THEN
			SET new_weather = "rainy";
		END IF;

		IF rand >= 40 AND rand < 60 THEN
			SET new_weather = "cloudy";
		END IF;
	
		IF rand >= 60 AND rand < 80 THEN
			SET new_weather = "windy";
		END IF;
	
		IF rand >= 80 AND rand < 90 THEN
			SET new_weather = "foggy";
		END IF;

		IF rand >= 90 AND rand < 95 THEN
			SET new_weather = "snowy";
		END IF;

		IF rand >= 95 AND rand < 100 THEN
			SET new_weather = "stormy";
		END IF;

		UPDATE planete SET weather = new_weather WHERE id = id_planete;
	UNTIL done
	END REPEAT;
	CLOSE curs_planete;
END$$
DELIMITER ;
-- PROCEDURE POUR METTRE A JOURS L'ANGLE DES PLANETES

DELIMITER $$
	DROP PROCEDURE IF EXISTS calc_angle$$

CREATE PROCEDURE calc_angle()
BEGIN
	DECLARE rayon, done, coord_x, coord_y, id_planete, mytime, nb_second, last_update_planete INT DEFAULT 0;
	DECLARE deviation, teta FLOAT DEFAULT 0.0;
	DECLARE curs_planete CURSOR FOR
	SELECT id, ROUND(SQRT(POW((x - 50), 2) + POW((y - 50), 2))) as rayon, last_update, angle FROM planete;
	DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
	SET mytime = (SELECT UNIX_TIMESTAMP());
	OPEN curs_planete;
	REPEAT FETCH curs_planete INTO id_planete, rayon, last_update_planete, teta;
		SET nb_second = (3600 * rayon) / 70;
		SET deviation = ((((mytime - last_update_planete) * 360) / nb_second) + teta) % 360;
		UPDATE planete SET angle = deviation, last_update = UNIX_TIMESTAMP() WHERE id = id_planete;
    UNTIL done
    END REPEAT;
 CLOSE curs_planete;
END$$
DELIMITER ;

-- PROCEDURE POUR METTRE A JOURS LES RESSOURCES DES PLANETES
DELIMITER $$
 DROP PROCEDURE IF EXISTS calc_resources$$
CREATE PROCEDURE calc_resources()

BEGIN

DECLARE pourcentage, surplus, done, laps_time, id_ressources, limit_metaux_ressources, limit_cristaux_ressources, limit_population_ressources INT DEFAULT 0;
DECLARE limit_tetranium_ressources, time_ressources, metaux_ressources, cristaux_ressources, population_ressources, terrain_metal, terrain_cristal, terrain_tetranium INT DEFAULT 0;
DECLARE tetranium_ressources, metaux_grow_ressources, cristaux_grow_ressources, population_grow_ressources, tetranium_grow_ressources INT DEFAULT 0;
DECLARE cristaux_productivity_ressources, metaux_productivity_ressources, tetranium_productivity_ressources INT DEFAULT 0;
DECLARE mytime, new_cristaux, new_metaux, new_population, new_tetranium INT DEFAULT 0;
DECLARE weather_planete VARCHAR(255) DEFAULT "sunny";
DECLARE race VARCHAR(25);
DECLARE curs_resources CURSOR FOR 
SELECT r.id, p.weather, r.last_date, r.limit_metaux, r.limit_cristaux, r.limit_population, r.limit_tetranium, r.metaux, r.cristaux, r.population, r.tetranium,
		r.metaux_grow, r.cristaux_grow, r.population_grow, r.tetranium_grow, r.cristaux_productivity, r.metaux_productivity, r.tetranium_productivity,
		SUM(pt.metal) as metal, SUM(pt.cristal) as cristal, SUM(pt.tetranium) as tetranium, u.race
		FROM ressources r 
			LEFT JOIN user u ON u.user_id = r.user_id
			LEFT JOIN planete p ON r.planet_id = p.id 
			LEFT JOIN planete_type_link ptl ON ptl.id_planet = p.id
			LEFT JOIN planete_type pt ON pt.id = ptl.id_planet_type
		WHERE r.user_id > 0;

DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

OPEN curs_resources;
REPEAT FETCH curs_resources INTO id_ressources, weather_planete, time_ressources, limit_metaux_ressources, 
		limit_cristaux_ressources, limit_population_ressources, limit_tetranium_ressources, metaux_ressources, 
		cristaux_ressources, population_ressources, tetranium_ressources, metaux_grow_ressources, cristaux_grow_ressources, 
		population_grow_ressources, tetranium_grow_ressources, cristaux_productivity_ressources, metaux_productivity_ressources, 
		tetranium_productivity_ressources, terrain_metal, terrain_cristal, terrain_tetranium, race;

SET mytime = (SELECT UNIX_TIMESTAMP());
SET laps_time = mytime - time_ressources;

SET new_metaux = metaux_ressources;
SET new_cristaux = cristaux_ressources;
SET new_population = population_ressources;
SET new_tetranium = tetranium_ressources;


## CALCUL POUR CROISSANCE OU DEGRADATION METAL 

IF metaux_ressources < limit_metaux_ressources THEN
	SET new_metaux = metaux_ressources + ((laps_time * (metaux_grow_ressources / 3600)) * (metaux_productivity_ressources / 100));
	SET new_metaux = new_metaux + (new_metaux * terrain_metal / 100);
	IF race = "mineur" THEN
		SET new_metaux = new_metaux + (new_metaux * 5 / 100);
	END IF;
ELSE
	SET pourcentage = 10;
	IF weather_planete = "rainy" OR weather_planete = "windy" THEN
		SET pourcentage = pourcentage + 5;
	END IF;
	IF weather_planete = "snowy" OR weather_planete = "stormy" THEN
		SET pourcentage = pourcentage + 10;
	END IF;
	SET surplus = metaux_ressources - limit_metaux_ressources;
	SET new_metaux = metaux_ressources - (laps_time * ((surplus * (pourcentage / 100)) / 3600));
	IF new_metaux < limit_metaux_ressources THEN
		SET new_metaux = limit_metaux_ressources;
	END IF;
	SET limit_metaux_ressources = new_metaux;
END IF;


## CALCUL POUR CROISSANCE OU DEGRADATION CRISTAL 
IF cristaux_ressources < limit_cristaux_ressources THEN
	SET new_cristaux = cristaux_ressources + ((laps_time * (cristaux_grow_ressources / 3600)) * (cristaux_productivity_ressources / 100));
	SET new_cristaux = new_cristaux + (new_cristaux * terrain_cristal / 100);
	IF race = "mineur" THEN
		SET new_cristaux = new_cristaux + (new_cristaux * 5 / 100);
	END IF;
ELSE
	SET pourcentage = 10;
	IF weather_planete = "rainy" OR weather_planete = "windy" THEN
		SET pourcentage = pourcentage + 5;
	END IF;
	IF weather_planete = "snowy" OR weather_planete = "stormy" THEN
		SET pourcentage = pourcentage + 10;
	END IF;
	SET surplus = cristaux_ressources - limit_cristaux_ressources;
	SET new_cristaux = cristaux_ressources - (laps_time * ((surplus * (pourcentage / 100)) / 3600));
	IF new_cristaux < limit_cristaux_ressources THEN
		SET new_cristaux = limit_cristaux_ressources;
	END IF;
	SET limit_cristaux_ressources = new_cristaux;
END IF;


## CALCUL POUR CROISSANCE OU DEGRADATION POPULATION

IF population_ressources < limit_population_ressources THEN
	SET new_population = population_ressources + ((laps_time * (population_grow_ressources / 3600)));
ELSE
	SET pourcentage = 20;
	SET surplus = population_ressources - limit_population_ressources;
	SET new_population = population_ressources - (laps_time * ((surplus * (pourcentage / 100)) / 3600));
	IF new_population = limit_population_ressources THEN
		SET new_population = limit_population_ressources;
	END IF;
	SET limit_population_ressources = new_population;
END IF;

## CALCUL POUR CROISSANCE OU DEGRADATION TETRANIUM
IF tetranium_ressources < limit_tetranium_ressources THEN
	SET new_tetranium = tetranium_ressources + ((laps_time * (tetranium_grow_ressources / 3600)) * (tetranium_productivity_ressources / 100));
	SET new_tetranium = new_tetranium + (new_tetranium * terrain_tetranium / 100);
	IF race = "mineur" THEN
		SET new_tetranium = new_tetranium + (new_tetranium * 5 / 100);
	END IF;
ELSE
	SET pourcentage = 15;
	IF weather_planete = "rainy" OR weather_planete = "windy" THEN
		SET pourcentage = pourcentage + 5;
	END IF;
	IF weather_planete = "snowy" OR weather_planete = "stormy" THEN
		SET pourcentage = pourcentage + 10;
	END IF;
	SET surplus = tetranium_ressources - limit_tetranium_ressources;
	SET new_tetranium = tetranium_ressources - (laps_time * ((surplus * (pourcentage / 100)) / 3600));
	IF new_tetranium < limit_tetranium_ressources THEN
		SET new_tetranium = limit_tetranium_ressources;
	END IF;
	SET limit_tetranium_ressources = new_tetranium;
END IF;

UPDATE ressources SET cristaux = LEAST(new_cristaux, limit_cristaux_ressources), tetranium = LEAST(new_tetranium, limit_tetranium_ressources),  metaux = LEAST(new_metaux, limit_metaux_ressources), population = LEAST(new_population, limit_population_ressources), last_date = UNIX_TIMESTAMP() WHERE id = id_ressources;
     UNTIL done
    END REPEAT;
 CLOSE curs_resources;
END$$
DELIMITER ;
