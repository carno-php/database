<?php
/**
 * Cluster test
 * User: moyo
 * Date: 2018/9/7
 * Time: 11:00 AM
 */

namespace Carno\Database\Tests;

use Carno\Cluster\Discover\Adaptors\Config as SD;
use Carno\Cluster\Resources;
use Carno\Config\Config;
use function Carno\Coroutine\async;
use Carno\Database\Clusters\MySQL;
use Carno\Database\Contracts\Executable;
use Carno\Database\Programs\Transaction;
use Carno\Database\Results\Created;
use Carno\Database\Results\Selected;
use Carno\Database\Results\Updated;
use Carno\Pool\Options;
use PHPUnit\Framework\TestCase;
use Closure;
use Exception;
use Throwable;

class ClusterTest extends TestCase
{
    /**
     * @var Resources
     */
    private $cluster = null;

    /**
     * @var MySQL
     */
    private $mysql = null;

    private function mysql() : MySQL
    {
        if ($this->mysql) {
            return $this->mysql;
        }

        $this->cluster = $cluster = new Resources(new SD($conf = new Config));

        $conf->set('mysql:test1', 'mysql://root@localhost/test?charset=utf8mb4');

        return $this->mysql = new class($cluster) extends MySQL {
            protected $server = 'test1';
            protected $timeout = 500;
            protected function options(string $service) : Options
            {
                return new Options;
            }
        };
    }

    private function go(Closure $closure)
    {
        async($closure)->catch(static function (Throwable $e) {
            echo 'FAILURE ', get_class($e), ' :: ', $e->getMessage(), PHP_EOL;
            echo $e->getTraceAsString();
            exit(1);
        });
        swoole_event_wait();
    }

    public function testExecute()
    {
        $this->go(function () {
            $mysql = $this->mysql();
            yield $this->cluster->startup()->ready();

            /**
             * @var Created $created
             */

            $created = yield $mysql->execute(
                'insert into items set `key` = ?key, `val` = ?val',
                ['key' => 'hello', 'val' => 'world']
            );

            $this->assertInstanceOf(Created::class, $created);

            $id = $created->id();
            $this->assertTrue($id > 0);

            /**
             * @var Selected $selected
             */

            $selected = yield $mysql->execute('select * from items where id = :id', ['id' => $id]);

            $this->assertInstanceOf(Selected::class, $selected);

            $this->assertEquals(1, $selected->count());
            $this->assertArraySubset((array)$selected, [[
                'id' => $id,
                'key' => 'hello',
                'val' => 'world',
            ]]);

            /**
             * @var Updated $updated
             */

            $updated = yield $mysql->execute('update items set val = "world2" where id = '.$id);

            $this->assertInstanceOf(Updated::class, $updated);
            $this->assertEquals(1, $updated->rows());

            /**
             * @var Updated $deleted
             */

            $deleted = yield $mysql->execute('delete from items where id = '.$id);

            $this->assertInstanceOf(Updated::class, $deleted);
            $this->assertEquals(1, $deleted->rows());

            // transactions

            // auto commit
            $tr1 = yield $mysql->transaction(function (Transaction $trans) {
                yield $this->insertTest($trans, 'test1');
                return 'test1';
            });
            $this->assertEquals('test1', $tr1);
            $this->assertEquals(1, yield $this->existsTest($mysql, 'test1'));

            // manual commit
            $tr2 = yield $mysql->transaction(function (Transaction $trans) {
                yield $this->insertTest($trans, 'test2');
                yield $trans->commit();
                return 'test2';
            });
            $this->assertEquals('test2', $tr2);
            $this->assertEquals(1, yield $this->existsTest($mysql, 'test2'));

            // manual rollback
            $tr3 = yield $mysql->transaction(function (Transaction $trans) {
                yield $this->insertTest($trans, 'test3');
                yield $trans->rollback();
                return 'test3';
            });
            $this->assertEquals('test3', $tr3);
            $this->assertEquals(0, yield $this->existsTest($mysql, 'test3'));

            // auto rollback
            $ee = $tr4 = null;
            try {
                $tr4 = yield $mysql->transaction(function (Transaction $trans) {
                    yield $this->insertTest($trans, 'test4');
                    throw new Exception('rollback');
                });
            } catch (Throwable $e) {
                $ee = $e;
            }
            $this->assertNull($tr4);
            $this->assertNotNull($ee);
            $this->assertEquals('rollback', $ee->getMessage());
            $this->assertEquals(0, yield $this->existsTest($mysql, 'test4'));

            yield $mysql->execute('TRUNCATE TABLE `items`');

            // shutdown
            yield $this->cluster->release();
        });
    }

    private function insertTest(Executable $link, string $key)
    {
        yield $link->execute('insert into items set `key` = :key, `val` = :val', [
            'key' => $key,
            'val' => rand(1000, 9999)
        ]);
    }

    private function existsTest(Executable $link, string $key)
    {
        /**
         * @var Selected $got
         */

        $got = yield $link->execute('select `val` from items where `key` = :key', ['key' => $key]);

        return $got->count();
    }
}
