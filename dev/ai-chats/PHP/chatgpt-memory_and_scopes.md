# Loop variables

> I know that in low level languages such as C, we initialize variables outside of loops to reuse the same memory slot in each iteration. Is it correct to do the same in PHP? How does the garbage collector handle such variables that are reused in loops?

Short answer: **usually no**. In PHP, manually moving variable declarations outside a loop for "memory reuse" is generally unnecessary and rarely improves performance.

Here's why.

### How PHP variables work

PHP is a managed language with:

* automatic memory management
* reference counting as the primary memory management mechanism
* a cycle-detecting garbage collector for circular references

A PHP variable is essentially a symbol pointing to a `zval` (the internal value container).

For example:

```php
foreach ($items as $item) {
    $result = process($item);
}
```

On each iteration, `$result` is simply assigned a new value. The previous value's reference count is decremented.

If it reaches zero:

* simple values (integers, strings, arrays without other references, etc.) are immediately destroyed.
* objects are destroyed immediately unless something else still references them.
* cyclic structures are handled later by the garbage collector.

So memory is naturally reclaimed without needing to declare `$result` outside the loop.

---

### Reusing the same variable

These two examples are effectively equivalent:

```php
foreach ($items as $item) {
    $temp = expensiveCalculation($item);
}
```

and

```php
$temp = null;

foreach ($items as $item) {
    $temp = expensiveCalculation($item);
}
```

PHP isn't allocating a new variable named `$temp` every iteration. The variable itself already exists within the scope; each assignment replaces the previous value.

So "declaring" it outside the loop doesn't save allocations in the way it might in lower-level languages.

---

### What happens to the old value?

Suppose:

```php
foreach ($items as $item) {
    $array = range(1, 100000);
}
```

Iteration 1:

```
$array --> large array
```

Iteration 2:

1. New array is created.
2. `$array` now points to the new array.
3. Old array's reference count becomes 0.
4. Old array is immediately freed.

The garbage collector is **not** involved here because there is no reference cycle.

---

### When does the garbage collector actually run?

The garbage collector only handles **cycles**.

For example:

```php
$a = new stdClass();
$b = new stdClass();

$a->b = $b;
$b->a = $a;

unset($a);
unset($b);
```

Neither object's reference count reaches zero because they reference each other.

The cycle collector eventually detects this and frees them.

This is independent of whether the variables were declared inside or outside a loop.

---

### Large loops

If you're processing millions of items:

```php
foreach ($rows as $row) {
    $image = loadHugeImage($row);
    process($image);
}
```

Memory usage generally stays stable because each previous `$image` is released before the next iteration, assuming nothing else retains a reference to it.

Only if you do something like:

```php
$images[] = $image;
```

will memory continually grow, because you're intentionally keeping references alive.

---

### When should you explicitly unset?

Normally, you don't need to.

Sometimes it's useful when a very large object won't be needed again before the end of a long-running function:

```php
$hugeData = loadHugeDataset();

process($hugeData);

unset($hugeData); // may free memory earlier
```

This can reduce peak memory usage in scripts that continue doing substantial work afterward.

---

### Comparison with C

In C:

```c
int x;

for (...) {
    x = ...
}
```

You avoid repeated stack allocation (although modern C compilers often optimize this away anyway).

In PHP, variables are managed dynamically. The "slot" for `$x` already exists in the function scope, and assignment replaces the value. The cost is dominated by creating and destroying the underlying values, not by where the variable first appears in the source code.

---

### Best practice

Write the code that's clearest:

```php
foreach ($orders as $order) {
    $invoice = generateInvoice($order);
    sendInvoice($invoice);
}
```

instead of:

```php
$invoice = null;

foreach ($orders as $order) {
    $invoice = generateInvoice($order);
    sendInvoice($invoice);
}
```

The second version doesn't provide a meaningful memory or performance benefit in normal PHP code.

The situations where memory management deserves extra attention are different from C:

* avoid keeping unnecessary references alive (e.g., appending everything to an array unintentionally),
* explicitly `unset()` large values only if you need to lower peak memory during a long-running process,
* stream or process data incrementally instead of loading everything into memory at once when working with large datasets.

# Other variables in loops

> Does this works too for variables that are not the loop variable but reassigned in each loop iteration?

Yes. In fact, that's the common case.

For example:

```php
foreach ($users as $user) {
    $data = loadUserData($user);
    $result = processData($data);
    $message = formatResult($result);

    echo $message;
}
```

Here, `$data`, `$result`, and `$message` are all reassigned on every iteration.

What happens internally is roughly:

1. `$data` is assigned a new value.
2. The previous value of `$data` has its reference count decremented.
3. If nothing else references that value, it is destroyed immediately.
4. The same process occurs for `$result` and `$message`.

The variable names (`$data`, `$result`, etc.) remain in the current scope for the lifetime of the function or script, but the values they point to are replaced on each assignment.

### Example with a large array

```php
foreach ($ids as $id) {
    $record = fetchLargeRecord($id);
    process($record);
}
```

Assuming `process()` doesn't keep a reference to `$record`, the memory usage stays roughly constant:

```
Iteration 1:
$record -> Record A

Iteration 2:
$record -> Record B
Record A is freed

Iteration 3:
$record -> Record C
Record B is freed
```

Only one large record is typically alive at a time.

### When memory does *not* get freed

Memory won't be reclaimed if something else still references the value:

```php
$all = [];

foreach ($ids as $id) {
    $record = fetchLargeRecord($id);
    $all[] = $record;
}
```

Now each record is also referenced by `$all`, so none of them can be destroyed until `$all` itself is destroyed or the elements are removed.

The same applies to objects:

```php
foreach ($ids as $id) {
    $obj = new MyObject();
    $cache[$id] = $obj;
}
```

Each object stays alive because `$cache` holds a reference.

### The important distinction

The variable itself is **not** created and destroyed every iteration. The **value** it refers to is.

So these are essentially equivalent in terms of memory management:

```php
foreach ($items as $item) {
    $temp = compute($item);
}
```

and

```php
$temp = null;

foreach ($items as $item) {
    $temp = compute($item);
}
```

Initializing `$temp` before the loop does not meaningfully change how PHP manages memory. The assignment inside the loop still replaces the old value, and if that old value has no remaining references, it is released immediately.

# Scopes

> So loops and conditions do not create new scopes in PHP?

Correct. **Loops and conditionals do not create a new variable scope in PHP.**

PHP has relatively simple scoping rules:

- Global scope
- Function/method scope
- Class scope (for properties, constants, etc.)
- Anonymous function (closure) scope

But constructs like `if`, `for`, `foreach`, `while`, `switch`, and `try` do **not** introduce a new scope.

For example:

```php
if (true) {
    $x = 42;
}

echo $x; // 42
```

Likewise:

```php
foreach ([1, 2, 3] as $n) {
    $square = $n * $n;
}

echo $square; // 9
```

Even though `$square` was first assigned inside the loop, it still exists afterward because the loop didn't create a new scope.

The same is true for `for`:

```php
for ($i = 0; $i < 3; $i++) {
    $value = $i;
}

echo $i;     // 3
echo $value; // 2
```

Both variables remain available after the loop.

### Functions do create a new scope

This is where variables become local:

```php
$x = 10;

function foo() {
    $x = 20;
    echo $x;
}

foo();    // 20
echo $x;  // 10
```

The `$x` inside `foo()` is completely separate from the global `$x`.

### Closures also create a scope

```php
$x = 10;

$fn = function () use ($x) {
    echo $x;
};

$fn(); // 10
```

Without `use ($x)`, the closure cannot access `$x` from the surrounding scope.

### Comparison to other languages

If you're coming from C, Java, JavaScript (`let`/`const`), or Rust, this behavior may feel unusual:

```c
{
    int x = 5;
}
// x no longer exists
```

In C, a block (`{ ... }`) creates a new scope.

In PHP:

```php
{
    $x = 5;
}

echo $x; // 5
```

The braces are just a grouping construct; they do not define a new variable scope.

This design is one reason why reusing variables by declaring them "outside the loop" has little meaning in PHP. Once a variable exists anywhere within a function's scope, assignments inside loops or conditionals simply replace its value rather than creating a new scoped variable.

# Loops and array transforms comparison

> I need to apply a transformation to each item of an array and store the result in a new array. What is the best for performances between using array_map and a closure, a foreach with an other array for the results, or modifying each element in the original array (for, foreach)?

For PHP, there are two separate questions:

1. **Which is fastest?**
2. **Which is the clearest?**

The answers aren't always the same.

### 1. `foreach` into a new array (usually the best balance)

```php
$result = [];

foreach ($items as $item) {
    $result[] = transform($item);
}
```

This is generally the **fastest or among the fastest** approaches and is very readable.

Advantages:

* minimal overhead
* easy to debug
* works with any transformation
* no callback invocation cost

For most code, this is the approach I'd recommend.

---

### 2. `array_map()`

```php
$result = array_map(fn($item) => transform($item), $items);
```

This is very expressive and functional.

The downside is that each element requires a callback invocation. Modern PHP has improved callback performance considerably (especially with OPcache and JIT), but there is still some overhead compared to a plain `foreach`.

If you're transforming a few hundred or a few thousand elements, you'll almost certainly never notice the difference.

---

### 3. Modify the original array in place

```php
foreach ($items as &$item) {
    $item = transform($item);
}
unset($item); // important
```

This avoids allocating a second array.

Advantages:

* lower peak memory usage
* potentially slightly faster because no second array is built

Disadvantages:

* modifies the input
* the reference form of `foreach` has some pitfalls, notably that `$item` remains a reference after the loop unless you `unset($item)`.

If you don't need the original array anymore, this is a good option.

---

### 4. Index-based `for`

```php
$count = count($items);

for ($i = 0; $i < $count; $i++) {
    $items[$i] = transform($items[$i]);
}
```

For numerically indexed arrays, this is fine, but it isn't generally faster than `foreach`. In fact, `foreach` is highly optimized for traversing PHP arrays.

---

## Memory considerations

Suppose you have one million items.

### New array

```php
$result = [];

foreach ($items as $item) {
    $result[] = transform($item);
}
```

For part of the execution you have:

* original array
* result array

Peak memory is roughly both arrays combined (plus temporary values).

### In place

```php
foreach ($items as &$item) {
    $item = transform($item);
}
unset($item);
```

Only one array exists, so peak memory is lower.

---

## What about copy-on-write?

PHP arrays use **copy-on-write**, so simply doing:

```php
$b = $a;
```

does **not** immediately duplicate the array. The copy only occurs if one of the arrays is modified.

However, when you intentionally create a transformed array:

```php
$result[] = transform($item);
```

you're creating a genuinely new array with new values, so copy-on-write doesn't eliminate the need for that second array.

---

## Performance ranking

For large arrays, the typical order is:

1. **`foreach`** (new array) — fastest or essentially tied for fastest.
2. **`foreach` by reference** (in place) — similarly fast and uses less memory if you don't need the original array.
3. **`array_map()`** — usually a bit slower because of callback overhead, though often by only a modest amount.

The exact differences depend on what `transform()` does. If the transformation is expensive (database calls, regexes, image processing, parsing, etc.), the loop overhead becomes negligible. If the transformation is trivial (for example, `fn($x) => $x + 1`), the callback overhead of `array_map()` is more noticeable.

### Recommendation

* If you **need to keep the original array**, use a `foreach` that builds a new result array.
* If you **don't need the original array**, transform it in place with `foreach` (and remember `unset($item)` afterward when using references).
* Use `array_map()` when it makes the code clearer and the transformation is a straightforward one-to-one mapping. The performance difference is usually not worth worrying about unless you've measured it and found it to be a bottleneck.
