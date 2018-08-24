<?php
/**
 * Executable API
 * User: moyo
 * Date: 11/12/2017
 * Time: 11:50 AM
 */

namespace Carno\Database\Contracts;

use Carno\Database\Results\Created;
use Carno\Database\Results\Selected;
use Carno\Database\Results\Updated;
use Carno\Promise\Promised;

interface Executable
{
    /**
     * @param string $sql
     * @param array $bind
     * @return Promised|Created|Updated|Selected
     */
    public function execute(string $sql, array $bind = []);

    /**
     * @deprecated
     * @param string $data
     * @return string
     */
    public function escape(string $data) : string;
}
