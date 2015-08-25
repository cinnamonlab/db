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

        if ( ( $host = Config::get($prefix . '.host') ) == null )
            throw new DBException('No Host Specified');
        if ( ( $dbname = Config::get($prefix . '.db')  )== null )
            throw new DBException('No DB Specified');
        if ( ( $user = Config::get($prefix . '.user')  )== null )
            throw new DBException('No User Name Specified');
        if ( ( $password = Config::get($prefix . '.pass')  )== null )
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

    function query( ) {
        if ( ! isset($this) )
            return self::connection()->query(func_get_args());

        $args = func_get_args();

        if ( is_array($args[0]) ) {
            $args = $args[0];
        }

        if ( ! isset($args[0]) ) throw new DBException('No SQL');

        if ( preg_match("/^select/i", $args[0]) )
            $query_type = self::QUERY_TYPE_SELECT;
        else if ( preg_match("/^update/i", $args[0] ) )
            $query_type = self::QUERY_TYPE_UPDATE;
        else if ( preg_match("/^delete/i", $args) )
            $query_type = self::QUERY_TYPE_DELETE;
        else if ( preg_match("/^insert/i", $args[0]) )
            $query_type = self::QUERY_TYPE_INSERT;
        else $query_type = self::QUERY_TYPE_OTHERS;

        if ( ( $query_type == self::QUERY_TYPE_DELETE
            || $query_type == self::QUERY_TYPE_UPDATE
            || $query_type == self::QUERY_TYPE_INSERT )
            && ($c = Config::get($this->prefix . '.write', null) ) != null ) {

            $this->pdo = self::connection($c)->getPdo();
        }

        $stmt = $this->pdo->prepare($args[0]);
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        if ( isset($args[1]) && is_array($args[1])) {
            $params = $args[1];
            reset($params);
            list($k) = each($params);
            if ( $k !== 0 ) {
                foreach($params as $key=>$value) {
                    if ( is_numeric($value) ) $mode = PDO::PARAM_INT; else $mode=PDO::PARAM_STR;
                    $stmt->bindValue($key, $value, $mode);
                }
                //HashMap type binding
            } else {
                foreach($params as $key => $value ) {
                    if ( is_numeric($value) ) $mode = PDO::PARAM_INT; else $mode=PDO::PARAM_STR;
                    $stmt->bindValue($key+1, $value, $mode);
                }
            }
        } else {
            for ( $i = 1; $i < count($args); $i++ ) {
                if ( is_numeric($args[$i]) ) $mode = PDO::PARAM_INT; else $mode=PDO::PARAM_STR;
                $stmt->bindValue($i, $args[$i], $mode);
            }
        }
        try {
            $now = microtime(true);
            $stmt->execute();
            array_push($this->log, array($args[0], ceil((microtime(true)-$now)*100000)/100));

        } catch ( \Exception $e ) {
            throw new DBException($e);
        }
        switch( $query_type ) {
            case self::QUERY_TYPE_SELECT:
                return $stmt->fetchAll();
            case self::QUERY_TYPE_INSERT:
                return $this->pdo->lastInsertId();
            case self::QUERY_TYPE_DELETE:
            case self::QUERY_TYPE_UPDATE:
                return $stmt->rowCount();
            default: return true;
        }
    }

    public function getPdo( ) {
        return $this->pdo;
    }
    public function select( ) {
        return $this->query( func_get_args() );
    }
    public function update( ) {
        return $this->query( func_get_args() );
    }
    public function delete( ) {
        return $this->query( func_get_args() );
    }
    public function insert( ) {
        return $this->query( func_get_args() );
    }

    private function getDefault( ) {
        $name = Config::get('database.default', null);
        if ( is_string($name) ) {
            return $name;
        }
        return "default";
    }

} 