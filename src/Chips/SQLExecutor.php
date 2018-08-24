<?php
/**
 * SQL executor
 * User: moyo
 * Date: 02/11/2017
 * Time: 6:00 PM
 */

namespace Carno\Database\Chips;

use Carno\Database\Results\Created;
use Carno\Database\Results\Selected;
use Carno\Database\Results\Updated;
use Carno\Pool\Wrapper\SAR;
use Carno\Pool\Wrapper\SRD;

trait SQLExecutor
{
    use SAR, SRD;

    /**
     * @param string $sql
     * @param array $bind
     * @return Created|Updated|Selected
     */
    public function execute(string $sql, array $bind = [])
    {
        return $this->sarRun($this->assigned($sql), 'execute', [$sql, $bind]);
    }

    /**
     * @deprecated
     * @param string $data
     * @return string
     */
    public function escape(string $data) : string
    {
        return $this->rndRun($this->assigned(), 'escape', [$data]);
    }
}
