<?php
/**
 * Executing exception
 * User: moyo
 * Date: 02/11/2017
 * Time: 3:14 PM
 */

namespace Carno\Database\Exception;

class ExecutingException extends ServerException
{
    /**
     * @param string $sql
     * @return static
     */
    public function proof(string $sql) : self
    {
        $this->message .= " [->] {$sql}";
        return $this;
    }
}
