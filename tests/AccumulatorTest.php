<?php

use PHPUnit\Framework\TestCase;

/**
 * MIT License
 *
 * Copyright (c) 2018 Adam Bjurstrom
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

class AccumulatorTest extends TestCase {

    public function test_seed_makes_number() {
        $object = new \Beerstrum\Accumulator\Accumulator();
        $seed   = $object->create_seed();

        $this->assertNotEmpty($seed);
        $this->assertRegExp('#^[0-9]+$#', $seed);
    }

    public function test_seed_makes_new_numbers() {
        $object = new \Beerstrum\Accumulator\Accumulator();
        $seed1  = $object->create_seed();
        $seed2  = $object->create_seed();

        $this->assertNotEquals($seed1, $seed2);
    }

    public function test_accumulate_works() {
        $object = new \Beerstrum\Accumulator\Accumulator();
        $seed   = "57808458560955605718711154358856228929385560312576663845370661406324640956711";

        $output = $object->accumulate($seed, 'foobar');

        $this->assertEquals("36832983030682430574292306451712089102797628290126052377254989189157144098783", $output);
    }

    public function test_accumulate_is_commutative() {
        $object = new \Beerstrum\Accumulator\Accumulator();
        $seed   = "57808458560955605718711154358856228929385560312576663845370661406324640956711";

        $r1a = $object->accumulate($seed, 'foobar');
        $r2a = $object->accumulate($r1a, 'barfoo');

        $r1b = $object->accumulate($seed, 'barfoo');
        $r2b = $object->accumulate($r1b, 'foobar');

        $this->assertTrue($r2a === $r2b);
    }

    public function test_witness() {
        $object = new \Beerstrum\Accumulator\Accumulator();
        $seed   = "57808458560955605718711154358856228929385560312576663845370661406324640956711";

        //Seed + 'a'
        $b_witness = $a = $object->accumulate($seed, 'a');
        //Seed + 'a' + 'b'
        $b  = $object->accumulate($a, 'b');
        //Seed + 'a' + 'b' + 'c'
        $c  = $object->accumulate($b, 'c');

        //Update b_witness with 'c': Seed + 'a' + 'c'
        $b_witness = $object->accumulate($b_witness, 'c');

        //Take the current state of the accumulator and prove membership by being able to add 'b' to the witness and get the current state.
        $this->assertTrue($object->accumulate($b_witness, 'b') === $c);
    }
}
