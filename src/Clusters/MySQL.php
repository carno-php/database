<?php
/**
 * Clustered MySQL
 * User: moyo
 * Date: 02/11/2017
 * Time: 12:05 PM
 */

namespace Carno\Database\Clusters;

use Carno\Cluster\Contracts\Tags;
use Carno\Cluster\Managed;
use Carno\Cluster\Resources;
use Carno\Database\Chips\SQLDetector;
use Carno\Database\Chips\SQLExecutor;
use Carno\Database\Chips\TransactionGuard;
use Carno\Database\Connectors\MySQL as Connector;
use Carno\Database\Contracts\Executable;
use Carno\Database\Options\Timeouts;
use Carno\DSN\DSN;
use Carno\Net\Endpoint;
use Carno\Pool\Options;
use Carno\Pool\Pool;
use Carno\Promise\Promised;

abstract class MySQL extends Managed implements Executable
{
    use SQLDetector, SQLExecutor, TransactionGuard;

    /**
     * @var array
     */
    protected $tags = [Tags::MASTER, Tags::SLAVE];

    /**
     * @var string
     */
    protected $type = 'mysql';

    /**
     * @var int
     */
    protected $port = 3306;

    /**
     * @var int
     */
    protected $timeout = 8500;

    /**
     * MySQL constructor.
     * @param Resources $resources
     */
    public function __construct(Resources $resources)
    {
        $resources->initialize($this->type, $this->server, $this);
    }

    /**
     * @param Endpoint $endpoint
     * @return Options
     */
    abstract protected function options(Endpoint $endpoint) : Options;

    /**
     * @param Endpoint $endpoint
     * @return Pool
     */
    protected function connecting(Endpoint $endpoint) : Pool
    {
        $vid = "{$this->type}:{$this->server}";

        $dsn = new DSN($endpoint->address()->host());

        $timeouts = new Timeouts(
            $dsn->option('connect', 1500),
            $dsn->option('execute', $this->timeout)
        );

        return new Pool($this->options($endpoint), static function () use ($timeouts, $dsn, $vid) {
            return new Connector(
                $dsn->host(),
                $dsn->port() ?: 3306,
                $dsn->user(),
                $dsn->pass(),
                $dsn->path(),
                $dsn->option('charset', 'utf8mb4'),
                $timeouts,
                $vid
            );
        }, $vid);
    }

    /**
     * @param Pool $connected
     * @return Promised
     */
    protected function disconnecting($connected) : Promised
    {
        return $connected->shutdown();
    }

    /**
     * @param string $sql
     * @return Pool
     */
    protected function assigned(string $sql = '') : Pool
    {
        return $this->picking(
            $this->clustered() && $this->readonly($sql)
                ? Tags::SLAVE
                : Tags::MASTER
        );
    }
}
