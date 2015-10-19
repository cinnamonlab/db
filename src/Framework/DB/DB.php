<?php

namespace Framework\DB;

use Framework\Config;
use Framework\DB\Exception\DBException;
use PDO;

class DB {

    const QUERY_TYPE_SELECT = 1;
    const QUERY_TYPE_INSERT = 2;
    const QUERY_TYPE_DELETE = 3;
    const QUERY_TYPE_UPDATE = 4;
    const QUERY_TYPE_OTHERS = 99;

    private static $dbs;
    private $pdo;
    private $prefix;

    private $log = array();

    private $fetchMode=PDO::FETCH_ASSOC;

    /**
     * Query Logs
     *
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }


    /**
     * @param $name
     */

    function __construct( $name = null ) {

        if ( ! $name ) $name = self::getDefault();
        $prefix = "database." . $name;
        if ( $name && ! Config::has($prefix) )
            throw new DBException('No Setting Found');

        $host = Config::get($prefix . '.host');
        if ( !isset($host) )
            throw new DBException('No Host Specified');

        $dbname = Config::get($prefix . '.db');
        if (!isset($dbname))
            throw new DBException('No DB Specified');

        $user = Config::get($prefix . '.user');
        if (!isset($user))
            throw new DBException('No User Name Specified');

        $password = Config::get($prefix . '.pass');
        if (!isset($password))
            throw new DBException('No Password Specified');

        $driver = Config::get($prefix . '.driver', 'mysql');
        $charset = Config::get($prefix . '.charset', 'utf8');
        $port = Config::get($prefix . '.port', "");
        if ( strlen($port) > 0 ) $port = ":" . $port;

        $this->pdo = new PDO(
            "$driver:host=$host$port;dbname=$dbname;charset=$charset",
            $user, $password);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$dbs[$name] = $this;
        $this->prefix = $prefix;

    }

    /**
     * @param $name
     * @return DB
     */
    static function connection( $name = null ) {
        if ( ! isset( self::$dbs[$name] ) ) self::$dbs[$name] = new DB($name);
        return self::$dbs[$name];
    }

    private function query($query_type,$sqlStatement,$params )
    {

        if (!isset($sqlStatement)) throw new DBException('No SQL');

        if (($query_type == self::QUERY_TYPE_DELETE
                || $query_type == self::QUERY_TYPE_UPDATE
                || $query_type == self::QUERY_TYPE_INSERT)
            && ($c = Config::get($this->prefix . '.write', null)) != null
        ) {

            $this->pdo = self::connection($c)->getPdo();
        }

        $stmt = $this->pdo->prepare($sqlStatement);
        $stmt->setFetchMode($this->fetchMode);
        if (!isset($params)) {
            $params = array();
        }
        if (!is_array($params)) $params = array($params);

        reset($params);
        list($k) = each($params);

        if ($k !== 0) {
            foreach ($params as $key => $value) {
                $mode = $this->getParamType($value);
                $stmt->bindValue($key, $value, $mode);
            }
            //HashMap type binding
        } else {
            foreach ($params as $key => $value) {
                $mode = $this->getParamType($value);
                $stmt->bindValue($key + 1, $value, $mode);
            }
        }

        try {
            $now = microtime(true);
            $stmt->execute();
            array_push($this->log, array($sqlStatement, ceil((microtime(true) - $now) * 100000) / 100));

        } catch (\Exception $e) {
            throw new DBException($e);
        }
        switch ($query_type) {
            case self::QUERY_TYPE_SELECT:
                return $stmt->fetchAll();
            case self::QUERY_TYPE_INSERT:
                return $this->pdo->lastInsertId();
            case self::QUERY_TYPE_DELETE:
            case self::QUERY_TYPE_UPDATE:
                return $stmt->rowCount();
            default:
                return true;
        }
    }

    private function getParamType($param) {
        if(is_numeric($param)) {
            return PDO::PARAM_INT;
        } elseif(is_bool($param)) {
            return PDO::PARAM_BOOL;
        } elseif(is_string($param)) {
            return PDO::PARAM_STR;
        } elseif(is_null($param)) {
            return PDO::PARAM_NULL;
        } else {
            return PDO::PARAM_STR;
        }
    }

    public function getPdo( ) {
        return $this->pdo;
    }
    public function select($sqlQuery,$param=array()) {
        return $this->query(self::QUERY_TYPE_SELECT,$sqlQuery,$param);
    }
    public function update($sqlQuery,$param=array() ) {
        return $this->query(self::QUERY_TYPE_UPDATE,$sqlQuery,$param);
    }
    public function delete($sqlQuery,$param=array() ) {
        return $this->query(self::QUERY_TYPE_DELETE,$sqlQuery,$param);
    }
    public function insert($sqlQuery,$param=array() ) {
        return $this->query(self::QUERY_TYPE_INSERT,$sqlQuery,$param);
    }

    private function getDefault( ) {
        $name = Config::get('database.default', null);
        if ( is_string($name) ) {
            return $name;
        }
        return "default";
    }

    public function setFetchMode($mode) {
        $this->fetchMode = $mode;
    }

} 