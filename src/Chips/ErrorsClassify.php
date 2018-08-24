<?php
/**
 * Errors classify
 * User: moyo
 * Date: 21/12/2017
 * Time: 3:24 PM
 */

namespace Carno\Database\Chips;

use Carno\Database\Exception\DuplicatedIndexException;
use Carno\Database\Exception\ExecutingException;

trait ErrorsClassify
{
    /**
     * @param string $sql
     * @param string $error
     * @param int $code
     * @return ExecutingException
     */
    protected function executingFail(string $sql, string $error, int $code) : ExecutingException
    {
        switch ($code) {
            case 1062:
                return (new DuplicatedIndexException($error, $code))->proof($sql);
            default:
                return (new ExecutingException($error, $code))->proof($sql);
        }
    }
}
