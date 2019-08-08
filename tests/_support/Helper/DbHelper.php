<?php
namespace Helper;

//namespace Codeception\Module;

/**
 * Additional methods for DB module
 */

class DbHelper extends \Codeception\Module
{
    public function getColumnFromDatabaseNoCriteria($table, $column)
    {
        $dbh = $this->getModule('Db')->dbh;
        $query = "select %s from %s";

        $query = sprintf($query, $column, $table);
        print_r($query);
        $this->debugSection('Query', $query);
        $sth = $dbh->prepare($query);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
}