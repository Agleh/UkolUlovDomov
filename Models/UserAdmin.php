<?php
/**
 * Created by PhpStorm.
 * User: Agleh
 * Date: 27.01.2019
 * Time: 13:51
 */

class UserAdmin
{
    private $settings = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
    );

    private $connection;

    //private $villages; //pole s městy, která jsou v databázi

    public function __construct()
    {
        $this->connection = new PDO("mysql:host=localhost;dbname=database_ulov_domov", 'root', '', $this->settings); // pro vyzkoušení
        //$this->villages = new village();
    }

    /**
     * @param string $user
     * @param array $cityRightsArray
     */
    public function set(string $user, array $cityRightsArray)
    {
        $villiges = [1 => 'Praha', 2 => 'Brno']; //zde bych měl pole měst z třídy village
        $query = $this->connection->prepare('
            SELECT *
            FROM `user_admin`
            WHERE `user_admin`.`first_name` = ?
        ');
        $params = array($user);
        $query->execute($params);
        if($query->fetchAll()) return; // ošetření zda už je v databázi
        $query = $this->connection->prepare('
            INSERT INTO `user_admin` (`first_name`)
            VALUES (?);'
        );
        $params = array($user);
        $query->execute($params);
        $userId = $this->connection->lastInsertId();
        $adressBookRight = $cityRightsArray['adressbook'];
        $searchRight = $cityRightsArray['search'];
        if (!(in_array(true, $adressBookRight))) $adressBookRight = array_map(function ($x) {
            return true;
        }, $adressBookRight);
        if (!(in_array(true, $searchRight))) $searchRight = array_map(function ($x) {
            return true;
        }, $searchRight);
        foreach ($villiges as $key => $village) {
            if (!($searchRight[$key] === false && $adressBookRight[$key] === false))
            {
                $query = $this->connection->prepare('
            INSERT INTO `user_village` (`user_id`, `village_id`, `search_right`, `adress_right`)
            VALUES (?, ?, ?, ?);'
                );
                $params = array($userId, $key, $searchRight[$key], $adressBookRight[$key]);
                $query->execute($params);
            }
        }
    }

    /**
     * @param string $user
     */
    public function addUser(string $user)
    {
        $villagesCount = 2; // normálně count($villages) podle pole měst z třídy village
        $arrayOfRights = array('adressbook' => array(), 'search' => array());
        for($i = 1; $i <= $villagesCount; $i++){
            $arrayOfRights['adressbook'][$i] = true;
            $arrayOfRights['search'][$i] = true;
        }
        $this->set($user, $arrayOfRights);
    }

    /**
     * @param string $user
     * @param string $city
     * @param int $right
     * @param bool $value
     */
    public function changeUsersRight(string $user, string $city, int $right, bool $value)
    {
        if($right)
        {
            $query = $this->connection->prepare('
        UPDATE `user_village`
        JOIN `user_admin` ON `user_admin`.`id` = `user_village`.`user_id`
        JOIN `village` ON `village`.`id` = `user_village`.`village_id`             
        SET `user_village`.`search_right` = ?
        WHERE `user_admin`.`first_name` = ? AND `village`.`name` = ?;
        ');
            $params = array($value, $user, $city);
            $query->execute($params);
        }
        else
        {
            $query = $this->connection->prepare('
        UPDATE `user_village`
        JOIN `user_admin` ON `user_admin`.`id` = `user_village`.`user_id`
        JOIN `village` ON `village`.`id` = `user_village`.`village_id`             
        SET `user_village`.`adress_right` = ?
        WHERE `user_admin`.`first_name` = ? AND `village`.`name` = ?;
        ');
            $params = array($value, $user, $city);
            $query->execute($params);
        }
        /**
         * tato sekvence kódu smaže řádek v user_village v případě, že uživatel nemá práva po změně
         */
        $query = $this->connection->prepare('
        DELETE `user_village` FROM `user_village`
        INNER JOIN `user_admin` on `user_admin`.`id` = `user_village`.`user_id`
        WHERE `user_admin`.`first_name` = ? AND `user_village`.`adress_right` = 0 AND `user_village`.`search_right` = 0 ;
        ');
        $params = array($user);
        $query->execute($params);
        /**
         * maže osobu z tabulky user_admin pokud již nemá žádná práva
         */
        $query = $this->connection->prepare('
        DELETE `user_admin` FROM `user_admin`
        INNER JOIN `user_vilage` ON `user_village`.`user_id` = `user_admin`.`id`
        WHERE `user_admin`.`first_name` = ? AND ( SELECT COUNT(*) 
                                                  FROM `user_village`
                                                  INNER JOIN `user_admin` ON `user_admin`.`id` = `user_village`.`user_id`
                                                  WHERE `user_admin`.`first_name` = ?;
                                                  ) = 0;
        ');
        $params = array($user, $user);
        $query->execute($params);
    }

    /**
     * @param string $user
     * @param int $right
     * @return array
     */
    public function get(string $user, int $right)
    {
        if($right){
            $query = $this->connection->prepare('
            SELECT `village`.`name`
            FROM `user_village`
            JOIN `user_admin` ON `user_admin`.`id` = `user_village`.`user_id`
            JOIN `village` ON `village`.`id` = `user_village`.`village_id`
            WHERE `user_admin`.`first_name` = ? AND `user_village`.`search_right` = true;
            '
            );
        }
        else {
            $query = $this->connection->prepare('
            SELECT `village`.`name`
            FROM `user_village`
            JOIN `user_admin` ON `user_admin`.`id` = `user_village`.`user_id`
            JOIN `village` ON `village`.`id` = `user_village`.`village_id`
            WHERE `user_admin`.`first_name` = ? AND `user_village`.`adress_right` = true;
            '
            );
        }
        $params = array($user);
        $result = $query->execute($params);
        return $query->fetchAll();
    }
}
