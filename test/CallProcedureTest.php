<?php

use Framework\DB\DB;

/**
 * Created by PhpStorm.
 * User: michael
 * Date: 10/19/15
 * Time: 13:49
 */
class CallProcedureTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var DB
     */
    private $db;
    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->db = DB::connection("read");
    }

    public function testCallProcedure() {
        $result = $this->db->query("call testNormal();");

        $this->assertTrue($result);
    }
    public function testCallProcedureWithParamObjectMode() {
        $this->db->setFetchMode(PDO::FETCH_OBJ);
        $result = $this->db->query("call test_param(?);",array(1),DB::QUERY_TYPE_SELECT);

        $this->assertTrue(is_array($result));

        foreach ($result as $obj) {
            $this->assertTrue(is_object($obj));
        }

    }
    public function testCallProcedureWithParamArrayMode() {
        $this->db->setFetchMode(PDO::FETCH_ASSOC);
        $result = $this->db->query("call test_param(?);",array(1),DB::QUERY_TYPE_SELECT);

        $this->assertTrue(is_array($result));

        foreach ($result as $obj) {
            $this->assertTrue(is_array($obj));
        }
    }
    public function testOutput() {
        $result = $this->db->query("call test_output(@out);");

        $this->assertTrue($result);

        $result = $this->db->select("select @out");

        $this->assertTrue(is_array($result));
    }
}
