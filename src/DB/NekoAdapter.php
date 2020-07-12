<?php

namespace Neko\Datatables\DB;

use Neko\Database\DB;
use Neko\Datatables\Query;


/**
 * Class LaravelAdapter
 * @package Neko\Datatables\DB
 */
class NekoAdapter extends DBAdapter
{
    /**
     * LaravelAdapter constructor.
     * @param null $config
     */
    public function __construct($config = null)
    {
    }

    /**
     * @return $this
     */
    public function connect()
    {
        return $this;
    }

    /**
     * @param Query $query
     * @return array
     */
    public function query(Query $query)
    {
        $data = DB::query($query, $query->escapes);
        $row = [];

        foreach ($data as $item) {
            $row[] = (array)$item;
        }

        return $row;
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function count(Query $query)
    {
        $data = DB::query($query, $query->escapes)->rowCount();
        return $data;
    }

    /**
     * @param $string
     * @param Query $query
     * @return string
     */
    public function escape($string, Query $query)
    {
        $query->escapes[':binding_'.(count($query->escapes) + 1)] = $string;
        return ':binding_'.count($query->escapes);
    }
}

