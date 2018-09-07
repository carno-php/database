<?php
/**
 * Transaction guard
 * User: moyo
 * Date: 03/11/2017
 * Time: 11:35 AM
 */

namespace Carno\Database\Chips;

use Carno\Database\Contracts\Executable;
use Carno\Database\Contracts\Transaction as TransAPI;
use Carno\Database\Exception\TransactionException;
use Carno\Database\Programs\Transaction as TransEXE;
use Carno\Pool\Pool;
use Carno\Pool\Poolable;
use Carno\Promise\Promise;
use Closure;
use Throwable;

trait TransactionGuard
{
    /**
     * @param Closure $program
     * @return mixed
     * @throws TransactionException
     */
    public function transaction(Closure $program)
    {
        /**
         * @var Pool $pool
         * @var TransAPI|Executable|Poolable $link
         */
        $pool = $this->assigned();
        $link = yield $pool->select();

        // assigned waiters
        $start = Promise::deferred();
        $commit = Promise::deferred();
        $rollback = Promise::deferred();

        // automatic release after commit
        $commit->then(static function () use ($link) {
            $link->release();
        });

        // automatic release after rollback
        $rollback->then(static function () use ($link) {
            $link->release();
        });

        // create session for transaction
        $session = new TransEXE($link, $start, $commit, $rollback);

        // wait for transaction started
        yield $session->begin();

        // flows
        try {
            // call user program
            $finished = yield $program($session);
            // automatic commit
            $commit->pended() && $rollback->pended() && yield $session->commit();
            // finished
            return $finished;
        } catch (Throwable $e) {
            // automatic rollback
            $rollback->pended() && yield $session->rollback();
            // throw to next
            throw $e;
        }
    }
}
