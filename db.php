<?php

class db
{
    var $link = null;

    function __construct($host, $port, $db, $user, $password)
    {
        $this->connect($host, $port, $db, $user, $password);
    }

    function connect($host, $port, $db, $user, $password)
    {
        $this->link = new mysqli($host, $user, $password, $db, $port);
        if ($this->link->connect_errno)
        {
            throw new RuntimeException('mysqli connection error: ' . $this->link->connect_error);
        }

        $this->link->set_charset('utf8mb4');
    }

    function query($sql)
    {
        if ($this->link == null)
        {
            throw new RuntimeException('mysqli connection error: Server not connected');
        }

        return $this->link->query($sql);
    }
}