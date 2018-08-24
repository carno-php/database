<?php
/**
 * DB Transaction
 * User: moyo
 * Date: 02/11/2017
 * Time: 2:47 PM
 */

namespace Carno\Database\Contracts;

use Carno\Promise\Promised;

interface Transaction
{
    /**
     * @return Promised
     */
    public function begin();

    /**
     * @return Promised
     */
    public function commit();

    /**
     * @return Promised
     */
    public function rollback();
}
