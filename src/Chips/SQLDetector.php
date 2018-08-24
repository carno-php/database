<?php
/**
 * SQL detector
 * User: moyo
 * Date: 02/11/2017
 * Time: 6:03 PM
 */

namespace Carno\Database\Chips;

trait SQLDetector
{
    /**
     * @param string $sql
     * @return bool
     */
    protected function readonly(string $sql) : bool
    {
        if (strtolower(substr($sql, 0, 7)) === 'select ') {
            return true;
        } else {
            return false;
        }
    }
}
