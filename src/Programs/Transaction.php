<?php
/**
 * Transaction program
 * User: moyo
 * Date: 03/11/2017
 * Time: 11:43 AM
 */

namespace Carno\Database\Programs;

use Carno\Database\Contracts\Executable;
use Carno\Database\Contracts\Transaction as TransAPI;
use Carno\Promise\Promised;

class Transaction implements TransAPI, Executable
{
    /**
     * @var TransAPI|Executable
     */
    private $link = null;

    /**
     * @var Promised
     */
    private $wStart = null;

    /**
     * @var Promised
     */
    private $wCommit = null;

    /**
     * @var Promised
     */
    private $wRollback = null;

    /**
     * Transaction constructor.
     * @param TransAPI $link
     * @param Promised $start
     * @param Promised $commit
     * @param Promised $rollback
     */
    public function __construct(
        TransAPI $link,
        Promised $start,
        Promised $commit,
        Promised $rollback
    ) {
        $this->link = $link;

        $this->wStart = $start;
        $this->wCommit = $commit;
        $this->wRollback = $rollback;
    }

    /**
     * @return Executable
     */
    public function link() : Executable
    {
        return $this->link;
    }

    /**
     * @param string $sql
     * @param array $bind
     * @return Promised
     */
    public function execute(string $sql, array $bind = [])
    {
        return $this->link->execute($sql, $bind);
    }

    /**
     * @deprecated
     * @param string $data
     * @return string
     */
    public function escape(string $data) : string
    {
        return $this->link->escape($data);
    }

    /**
     * @return Promised
     */
    public function begin()
    {
        yield $this->link->begin();
        $this->wStart->resolve();
        return $this->wStart;
    }

    /**
     * @return Promised
     */
    public function commit()
    {
        yield $this->link->commit();
        $this->wCommit->resolve();
        return $this->wCommit;
    }

    /**
     * @return Promised
     */
    public function rollback()
    {
        yield $this->link->rollback();
        $this->wRollback->resolve();
        return $this->wRollback;
    }
}
