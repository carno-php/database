<?php
/**
 * Params bind test
 * User: moyo
 * Date: 23/03/2018
 * Time: 2:12 PM
 */

namespace Carno\Database\Tests\Chips;

use Carno\Database\Chips\ParamsBind;
use PHPUnit\Framework\TestCase;

class ParamsBindTest extends TestCase
{
    use ParamsBind;

    private function escape(string $string) : string
    {
        return $string;
    }

    public function testBinding()
    {
        $this->assertEquals('"A","B","C"', $this->binding('?0,?1,?2', ['A', 'B', 'C']));
        $this->assertEquals('"A","B","C"', $this->binding(':a,:b,:c', ['a' => 'A', 'b' => 'B', 'c' => 'C']));
        $this->assertEquals('"A","B","C"', $this->binding(':a,?1,:c', ['a' => 'A', 1 => 'B', 'c' => 'C']));

        $this->assertEquals('"?1",":c",":a"', $this->binding(':a,?1,:c', ['a' => '?1', 1 => ':c', 'c' => ':a']));

        $this->assertEquals(
            ':test:test2:"final""1"?999',
            $this->binding(
                ':test:test2::test3?888?999',
                ['test' => [], 'test2' => $this, 'test3' => 'final', 888 => true]
            )
        );
    }
}
