<?php

namespace TESTS;

require __DIR__ . '/../vendor/autoload.php';

use Carno\Cluster\Discover\Adaptors\Config as ConfigSD;
use Carno\Cluster\Resources;
use Carno\Config\Config;
use function Carno\Coroutine\go;
use Carno\Database\Clusters\MySQL;
use Carno\Database\Programs\Transaction;
use Carno\Pool\Options;

$cluster = new Resources(new ConfigSD($conf = new Config));

$mysql = new class($cluster) extends MySQL {
    protected $server = 'test1';
    protected $timeout = 500;
    protected function options(string $service) : Options
    {
        return new Options;
    }
};

$conf->set('mysql:test1', 'mysql://root@localhost/test?charset=utf8mb4');

go(static function () use ($cluster, $mysql) {
    yield $cluster->startup()->ready();

    logger()->debug('PING#1', (array) yield $mysql->execute('select 1 as ping'));

    yield $mysql->transaction(function (Transaction $trans) {
        logger()->debug('PING#2', (array) yield $trans->execute('select 2 as ping'));
    });

    yield $cluster->release();
});
