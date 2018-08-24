<?php
/**
 * Connection exception
 * User: moyo
 * Date: 2018/7/30
 * Time: 10:36 AM
 */

namespace Carno\Database\Exception;

use Carno\Pool\Contracts\Broken;
use RuntimeException;

abstract class ConnectionException extends RuntimeException implements Broken
{

}
