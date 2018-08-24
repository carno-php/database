<?php
/**
 * Timeouts option
 * User: moyo
 * Date: 2018/7/27
 * Time: 6:33 PM
 */

namespace Carno\Database\Options;

class Timeouts
{
    /**
     * @var int
     */
    private $connect = null;

    /**
     * @var int
     */
    private $execute = null;

    /**
     * Timeouts constructor.
     * @param int $connect
     * @param int $execute
     */
    public function __construct(int $connect = 3500, int $execute = 35000)
    {
        $this->connect = $connect;
        $this->execute = $execute;
    }

    /**
     * @return int
     */
    public function connect() : int
    {
        return $this->connect;
    }

    /**
     * @return int
     */
    public function execute() : int
    {
        return $this->execute;
    }
}
