<?php
/**
 * Created result
 * User: moyo
 * Date: 02/11/2017
 * Time: 3:03 PM
 */

namespace Carno\Database\Results;

class Created
{
    /**
     * @var int
     */
    private $id = null;

    /**
     * Created constructor.
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function id() : int
    {
        return $this->id;
    }
}
