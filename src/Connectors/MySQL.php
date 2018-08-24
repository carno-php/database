<?php
/**
 * MySQL connector
 * User: moyo
 * Date: 20/10/2017
 * Time: 5:51 PM
 */

namespace Carno\Database\Connectors;

use function Carno\Coroutine\await;
use function Carno\Coroutine\ctx;
use Carno\Database\Chips\ErrorsClassify;
use Carno\Database\Chips\ParamsBind;
use Carno\Database\Contracts\Executable;
use Carno\Database\Contracts\Transaction;
use Carno\Database\Exception\ConnectingException;
use Carno\Database\Exception\TimeoutException;
use Carno\Database\Exception\TransactionException;
use Carno\Database\Exception\UplinkException;
use Carno\Database\Options\Timeouts;
use Carno\Database\Results\Created;
use Carno\Database\Results\Selected;
use Carno\Database\Results\Updated;
use Carno\Pool\Managed;
use Carno\Pool\Poolable;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use Carno\Tracing\Contracts\Vars\EXT;
use Carno\Tracing\Contracts\Vars\TAG;
use Carno\Tracing\Standard\Endpoint;
use Carno\Tracing\Utils\SpansCreator;
use Swoole\MySQL as SWMySQL;

class MySQL implements Poolable, Executable, Transaction
{
    use Managed, ParamsBind, ErrorsClassify, SpansCreator;

    /**
     * @var Timeouts
     */
    private $timeout = null;

    /**
     * @var string
     */
    private $named = null;

    /**
     * @var string
     */
    private $host = null;

    /**
     * @var int
     */
    private $port = null;

    /**
     * @var string
     */
    private $username = null;

    /**
     * @var string
     */
    private $password = null;

    /**
     * @var string
     */
    private $database = null;

    /**
     * @var string
     */
    private $charset = null;

    /**
     * @var SWMySQL
     */
    private $link = null;

    /**
     * MySQL constructor.
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $charset
     * @param Timeouts $timeout
     * @param string $named
     */
    public function __construct(
        string $host,
        int $port,
        string $username,
        string $password,
        string $database,
        string $charset = 'utf8mb4',
        Timeouts $timeout = null,
        string $named = 'mysql'
    ) {
        $this->host = $host;
        $this->port = $port;

        $this->username = $username;
        $this->password = $password;

        $this->database = $database;
        $this->charset = $charset;

        $this->named = $named;
        $this->timeout = $timeout ?? new Timeouts;

        ($this->link = new SWMySQL)->on('close', function () {
            $this->closed()->resolve();
        });
    }

    /**
     * @return array
     */
    private function options() : array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'user' => $this->username,
            'password' => $this->password,
            'database' => $this->database,
            'charset' => $this->charset,
            'timeout' => round($this->timeout->connect() / 1000, 3),
        ];
    }

    /**
     * @return Promised
     */
    public function connect() : Promised
    {
        return new Promise(function (Promised $promise) {
            $this->link->connect($this->options(), static function (SWMySQL $db, bool $success) use ($promise) {
                $success
                    ? $promise->resolve()
                    : $promise->throw(new ConnectingException($db->connect_error, $db->connect_errno))
                ;
            });
        });
    }

    /**
     * @return Promised
     */
    public function heartbeat() : Promised
    {
        return new Promise(function (Promised $promised) {
            $this->link->query('SELECT 1', function (SWMySQL $db, $result) use ($promised) {
                (is_array($result) && count($result) === 1)
                    ? $promised->resolve()
                    : $promised->reject()
                ;
            });
        });
    }

    /**
     * @return Promised
     */
    public function close() : Promised
    {
        $this->link->close();
        return $this->closed();
    }

    /**
     * @param string $sql
     * @param array $bind
     * @return Promised
     */
    public function execute(string $sql, array $bind = [])
    {
        $this->traced() && $this->newSpan($ctx = clone yield ctx(), 'sql.execute', [
            TAG::SPAN_KIND => TAG::SPAN_KIND_RPC_CLIENT,
            TAG::DATABASE_TYPE => 'mysql',
            TAG::DATABASE_INSTANCE => sprintf('%s:%d', $this->host, $this->port),
            TAG::DATABASE_USER => $this->username,
            TAG::DATABASE_STATEMENT => $sql,
            EXT::REMOTE_ENDPOINT => new Endpoint($this->named),
        ]);

        if ($bind) {
            $sql = $this->binding($sql, $bind);
        }

        $executor = function ($fn) use ($sql) {
            if (false === $this->link->query($sql, $fn)) {
                throw new UplinkException('Unknown failure');
            }
        };

        $receiver = function (SWMySQL $db, $result) use ($sql) {
            if (is_bool($result)) {
                if ($result) {
                    if ($db->insert_id) {
                        return new Created($db->insert_id);
                    } else {
                        return new Updated($db->affected_rows);
                    }
                } else {
                    throw $this->executingFail($sql, $db->error, $db->errno);
                }
            } else {
                return new Selected($result);
            }
        };

        return $this->finishSpan(
            await(
                $executor,
                $receiver,
                $this->timeout->execute(),
                TimeoutException::class,
                sprintf('SQL [->] %s', $sql)
            ),
            $ctx ?? null
        );
    }

    /**
     * @param string $data
     * @return string
     */
    public function escape(string $data) : string
    {
        return is_numeric($data) ? $data : ($data ? $this->link->escape($data) : '');
    }

    /**
     * @return Promised
     */
    public function begin()
    {
        return $this->transCMD('begin');
    }

    /**
     * @return Promised
     */
    public function commit()
    {
        return $this->transCMD('commit');
    }

    /**
     * @return Promised
     */
    public function rollback()
    {
        return $this->transCMD('rollback');
    }

    /**
     * @param string $func
     * @return Promised
     */
    private function transCMD(string $func)
    {
        $this->traced() && $this->newSpan($ctx = clone yield ctx(), "trx.{$func}", [
            SPAN_KIND => SPAN_KIND_RPC_CLIENT,
            DATABASE_TYPE => 'mysql',
            DATABASE_INSTANCE => sprintf('%s:%d', $this->host, $this->port),
            DATABASE_USER => $this->username,
            DATABASE_STATEMENT => $func,
            EXT::REMOTE_ENDPOINT => new Endpoint($this->named),
        ]);

        $executor = function ($fn) use ($func) {
            if (false === $this->link->$func($fn)) {
                throw new UplinkException('Unknown failure');
            }
        };

        $receiver = static function (SWMySQL $db, bool $result) {
            if ($result) {
                return;
            } else {
                throw new TransactionException($db->error, $db->errno);
            }
        };

        return $this->finishSpan(
            await(
                $executor,
                $receiver,
                $this->timeout->execute(),
                TimeoutException::class,
                sprintf('TRANS [->] %s', $func)
            ),
            $ctx ?? null
        );
    }
}
