<?php

class Database extends MysqliDb
{
    protected ?MysqliDb $db;
    protected static ?Database $instance = null;

    protected function __construct()
    {
        parent::__construct(
            host: 'localhost',
            username: 'loc',
            password: '1234',
            db: 'crypto'
        );
    }

    protected function __clone()
    {
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}