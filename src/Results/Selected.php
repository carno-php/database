<?php
/**
 * Selected result
 * User: moyo
 * Date: 02/11/2017
 * Time: 3:08 PM
 */

namespace Carno\Database\Results;

use ArrayObject;

class Selected extends ArrayObject
{
    /**
     * Selected constructor.
     * @param array $rows
     */
    public function __construct(array $rows)
    {
        parent::__construct($rows);
    }
}
