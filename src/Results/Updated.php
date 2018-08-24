<?php
/**
 * Updated result
 * User: moyo
 * Date: 02/11/2017
 * Time: 3:04 PM
 */

namespace Carno\Database\Results;

class Updated
{
    /**
     * @var int
     */
    private $rows = null;

    /**
     * Updated constructor.
     * @param int $rows
     */
    public function __construct(int $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return int
     */
    public function rows() : int
    {
        return $this->rows;
    }
}
