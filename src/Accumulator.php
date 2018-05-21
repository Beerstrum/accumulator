<?php
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

namespace Beerstrum\Accumulator;

/**
 * Class Accumulator
 *
 * A class which uses GMP to create and update a cryptographic accumulator.
 *
 * @package Beerstrum\Accumulator
 */

class Accumulator {

    /** @var resource $max_ring_size Holds the upper bound of the big ring.  Default: 2^$hash_bit_count */
    protected $max_ring_size;
    /** @var string $hash_type a valid hash algorithm identifier.  Default: sha256 */
    protected $hash_type;
    /** @var int $hash_bit_count the number of bits in the hash.  Default: 256 */
    protected $hash_bit_count;

    /**
     * Accumulator constructor.
     *
     * @param array $config  (Optional) an array of configuration values.  Possible properties include:
     *                       hash_type      => string name of hash algo to use
     *                       hash_bit_count => int number of bits hash outputs
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct($config = []) {

        if (!extension_loaded('gmp')) {
            throw new \RuntimeException('GMP extension does not appear to be loaded and it is required.', 15);
        }

        $this->hash_type      = 'sha256';
        $this->hash_bit_count = 256;

        if (!empty($config)) {

            foreach ($config as $key => $value) {

                if (property_exists($this, $key)) {
                    $this->{$key} = $value;
                }
            }
        }

        if (empty($this->hash_type) || empty($this->hash_bit_count)) {
            throw new \InvalidArgumentException('Hash type or hash bit count was not set.', 30);
        }

        $this->max_ring_size = gmp_pow(gmp_init(2), $this->hash_bit_count);
    }

    /**
     * Builds a starting point for an accumulator.
     *
     * @return string representation of an int number which is the starting point of the accumulator.
     */
    public function create_seed() {

        $limiter = ceil($this->hash_bit_count / 16);

        if (empty($limiter)) {
            $limiter = 20;
        }

        $s1a = gmp_random($limiter);
        $s1b = gmp_random($limiter);
        $s2a = gmp_random($limiter);
        $s2b = gmp_random($limiter);

        $s1_prime = gmp_nextprime(gmp_mul($s1a, $s1b));
        $s2_prime = gmp_nextprime(gmp_mul($s2a, $s2b));

        $seed = gmp_mul($s1_prime, $s2_prime);

        $seed = $this->ring_big_numbers($seed);

        return gmp_strval($seed);
    }

    /**
     * @param string|resource $accumulator An existing accumulator created by the create_seed method with any number of inputs added to it
     * @param string          $input       Some kind of input to add to the accumulator, must be string or object with __toString method.
     *
     * @return string
     */
    public function accumulate($accumulator, $input) {

        /**
         *
         */
        if (version_compare(PHP_VERSION, '5.6.3') >= 0) {
            $unpack_format = 'Q*';
        } else {
            $unpack_format = 'i*';
        }

        $ints = unpack($unpack_format, hash($this->hash_type, $input, true));

        $x = gmp_init(1);

        foreach ($ints as $index => $int) {

            if ($int === 0) {
                $ints[$index] = 1;
            }

            $a = gmp_init($ints[1]);
            $x = gmp_mul($x, $a);
        }

        /**
         * This attempts to prevent collisions if all x gets mapped to the next prime.
         * X is cast to absolute due to lack of a true a unsigned int value.
         */
        $x_prime = gmp_nextprime(gmp_abs($x));

        $accumulator = gmp_init($accumulator);
        $accumulator = gmp_mul($accumulator, $x_prime);

        $accumulator = $this->ring_big_numbers($accumulator);

        return gmp_strval($accumulator);
    }

    /**
     * Calculate the big z ring modulo remainder.
     *
     * @param resource|string $input  A number to move into the big ring space.
     *
     * @return resource A GMP number resource with the remainder inside the big ring space.
     */
    protected function ring_big_numbers($input) {
        $int_max = $this->max_ring_size;

        if (gmp_cmp($input, $int_max) > 0) {
            $input = gmp_mod($input, $int_max);
        }

        return $input;
    }
}
