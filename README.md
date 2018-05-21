# Accumulator: Fixed Size Cryptographic Membership

**What Does It Do?**

Using Accumulator you can securely* prove that some input
was added to an accumulator by accumulating an up to date witness 
and a value.

**How do I use it?**

To collect values:

1. Create a new seed.
4. Record the state of the accumulator as a witness for a new value to be added.
5. Add the value to the accumulator.
6. Update all other witnesses with the new value added.

To prove* membership of a value:

1. Supply a value and a witness.
2. Combine the value and the witness.
3. Check for equality with the accumulator. 

```
$object      = new \Beerstrum\Accumulator\Accumulator();
$accumulator = $object->create_seed();

$foobar_witness = $accumulator;
$accumulator    = $object->accumulate($accumulator, 'foobar');

$bazfoo_witness = $accumulator;
$accumulator    = $object->accumulate($accumulator, 'bazfoo');
$foobar_witness = $object->accumulate($foobar_witness, 'bazfoo');

$membership = $object->accumulate($foobar_witness, 'foobar') == $accumulator;
```

**What Is a Witness?**

A whiteness is a copy of the accumulator without the input you want 
to prove is a member.  The idea is built on the idea that the accumulator
is commutative, you can add stuff in any order at the result is the same.
If one can provide a witness and a value which when accumulated is equal to
the current state of the accumulator, it proves* that the value was added into
the accumulator.

This works in most cases because the accumulator tries to avoid collisions by
mapping all values added to the accumulator to the next prime* number.

Note that all witnesses need to be updated with new values added to the accumulator.
This very much is a storage for computation time trade off.

**Is This The Blockchain?!**

No.  It might help deal with some issues that decentralized ledgers run into, but put your
checkbooks away, its not the Blockchain.

***Is It Secure?**

No.  Everything about this violates the first rule of encryption: "Don't roll your own crypto".

To name a few problems I can think of off the top of my head: The random sources aren't perfect.  Second, get next prime function this uses isn't perfect 
("This function uses a probabilistic algorithm to identify primes and chances to get a composite number are extremely small.")
Then of course all the side channel attacks I'm sure exist somewhere in there.

If you want to use this for anything involving money, my advice is, don't. 
