<?php

namespace Tests\Unit;

use App\Support\FractionalIndex;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FractionalIndexTest extends TestCase
{
    public function test_it_matches_the_reference_implementation()
    {
        // Expected values generated with the rocicorp/fractional-indexing JS package.
        $this->assertSame('a0', FractionalIndex::between(null, null));
        $this->assertSame('a1', FractionalIndex::between('a0', null));
        $this->assertSame('a2', FractionalIndex::between('a1', null));
        $this->assertSame('Zz', FractionalIndex::between(null, 'a0'));
        $this->assertSame('a0V', FractionalIndex::between('a0', 'a1'));
        $this->assertSame('a1V', FractionalIndex::between('a1', 'a2'));
        $this->assertSame('a0l', FractionalIndex::between('a0V', 'a1'));
        $this->assertSame('b00', FractionalIndex::between('az', null));
        $this->assertSame('Zy', FractionalIndex::between(null, 'Zz'));
        $this->assertSame('Yzz', FractionalIndex::between(null, 'Z0'));
        $this->assertSame('a0', FractionalIndex::between(null, 'a0V'));
        $this->assertSame('a1', FractionalIndex::between('a0', 'a1V'));
        $this->assertSame('Zz', FractionalIndex::between('Zy', 'a0'));
        $this->assertSame('a0', FractionalIndex::between('Zz', 'a1'));
        $this->assertSame('a0', FractionalIndex::between('Zz', 'a01'));
        $this->assertSame('a0', FractionalIndex::between(null, 'a01'));
    }

    public function test_keys_stay_in_order_through_repeated_inserts_into_the_same_gap()
    {
        $keys = [FractionalIndex::between(null, null)];
        $keys[] = FractionalIndex::between($keys[0], null);

        // Always insert right after the first key: the worst case for key length.
        for ($i = 0; $i < 500; $i++) {
            array_splice($keys, 1, 0, [FractionalIndex::between($keys[0], $keys[1])]);
        }

        $sorted = $keys;
        usort($sorted, strcmp(...));

        $this->assertSame($sorted, $keys);
        $this->assertSame(count($keys), count(array_unique($keys)));
    }

    public function test_it_rejects_keys_in_the_wrong_order()
    {
        $this->expectException(InvalidArgumentException::class);

        FractionalIndex::between('a1', 'a0');
    }

    public function test_it_rejects_equal_keys()
    {
        $this->expectException(InvalidArgumentException::class);

        FractionalIndex::between('a1', 'a1');
    }

    public function test_it_rejects_malformed_keys()
    {
        $this->expectException(InvalidArgumentException::class);

        FractionalIndex::between('a00', null);
    }

    public function test_it_rejects_keys_with_characters_outside_the_alphabet()
    {
        $this->expectException(InvalidArgumentException::class);

        FractionalIndex::between('a-', null);
    }

    public function test_it_places_a_key_at_an_index()
    {
        $keys = ['a0', 'a1', 'a2'];

        $this->assertSame('Zz', FractionalIndex::forIndex($keys, 0));
        $this->assertSame('a0V', FractionalIndex::forIndex($keys, 1));
        $this->assertSame('a3', FractionalIndex::forIndex($keys, 3));
        $this->assertSame('a3', FractionalIndex::forIndex($keys, 99));
        $this->assertSame('a0', FractionalIndex::forIndex([], 5));
    }
}
