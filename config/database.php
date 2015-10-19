<?php

return array(
    "default"=>"read",

    "read" => array(
        "driver"    => "mysql",
        "host"      => "localhost",
        "db"        => "test",
        "user"      => "root",
        "pass"      => "",
        "port"      => 3306,
        'write'     => "write"
    ),

    "write" => array(
        "driver"    => "mysql",
        "host"      => "localhost",
        "db"        => "test",
        "user"      => "root",
        "pass"      => "",
        "port"      => 3306
    ),

);