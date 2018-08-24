<?php
/**
 * Params binding
 * User: moyo
 * Date: 15/12/2017
 * Time: 3:59 PM
 */

namespace Carno\Database\Chips;

trait ParamsBind
{
    /**
     * @var string
     */
    private $pbks = '{![%s]-}';

    /**
     * @param string $sql
     * @param array $params
     * @return string
     */
    protected function binding(string $sql, array $params) : string
    {
        // sort by key length (make correct of ":test,:test2")
        uksort($params, static function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        // prepare slots
        foreach ($params as $name => $data) {
            is_scalar($data) && $sql = str_ireplace([":{$name}", "?{$name}"], sprintf($this->pbks, $name), $sql);
        }

        // data replaced
        foreach ($params as $name => $data) {
            is_scalar($data) && $sql = str_ireplace(sprintf($this->pbks, $name), '"'.$this->escape($data).'"', $sql);
        }

        return $sql;
    }
}
