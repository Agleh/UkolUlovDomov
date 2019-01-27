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

    public function __construct()
    {
        $this->connection = new PDO("mysql:host=localhost;dbname=database_ulov_domov", 'root', '', $this->settings);
    }

    /**
     * @param string $user
     * @param array $cityRightsArray
     */
    public function set(string $user, array $cityRightsArray)
    {
        $villiges = [1 => 'Praha', 2 => 'Brno'];
        $query = $this->connection->prepare('
            INSERT INTO `user_admin` (`first_name`)
            VALUES (?);'
        );
        $params = array($user);
        $query->execute($params);
        $userId = $this->connection->lastInsertId();
        $addressBookRight = $cityRightsArray['addressbook'];
        $searchRight = $cityRightsArray['search'];
        if (!(in_array(true, $addressBookRight))) $addressBookRight = array_map(function ($x) {
            return true;
        }, $addressBookRight);
        if (!(in_array(true, $searchRight))) $searchRight = array_map(function ($x) {
            return true;
        }, $searchRight);
        foreach ($villiges as $key => $village) {
            if (!($searchRight[$key] === false && $addressBookRight[$key] === false))
            {
                $query = $this->connection->prepare('
            INSERT INTO `user_village` (`user_id`, `village_id`, `search_right`, `adress_right`)
            VALUES (?, ?, ?, ?);'
                );
                $params = array($userId, $key, $searchRight[$key], $addressBookRight[$key]);
                $query->execute($params);
            }
        }
    }

    /**
     * @param string $user
     * @param int $right
     * @return bool
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
            SELECT *
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

//$x = new UserAdmin();
//$x->set('Petu', [ 'addressbook' => [ 1 => true, 2 => false ] , 'search' => [ 1 => false, 2 => false ] ]);
//var_dump($x->get('Petu', 0));
