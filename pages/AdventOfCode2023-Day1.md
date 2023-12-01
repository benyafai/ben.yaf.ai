# Advent Of Code

### 2023 - Day 1

___

I'm new to #AdventOfCode, it popped up on my Mastodon timeline and I thought why not take part?

Part one I thought was quite simple.
I saved the list as a txt file, opened it and iterated over each line.

The magic was this:

```
// Regex to remove non-digits.
$filtered = preg_replace('/[^0-9]/', '$1', $line);
// Sum the first character and last character
$sum += (int) (substr($filtered, 0, 1) . substr($filtered, -1));
```

___

Like others, I had a bit of trouble on part two parsing the `twone` and `oneight` strings.

I ended up finding the position of each word-number and seeing which was earliest, then replacing only that instance with the integer.

Then, remove all non-digits and get our first and last digits in the string.

```
$firstNum = '';
$firstVal = $firstPos = strlen($first) + 9;
foreach($numList as $num => $val) {
    // Find the number from reverse, keep track of the 'first' number in the string
    $pos = strpos($first, $num);
    if ($pos !== false && $pos < $firstPos) {
        $firstPos = $pos;
        $firstVal = $val;
        $firstNum = $num;
    }
}
// If we have a string, then we want to replace that with the actual value
if ($firstVal < strlen($first) + 9) {
    $first = substr_replace($first, $firstVal, $firstPos, strlen($firstNum));
}
// Then we filter out non digits
$first = preg_replace('/[^0-9]/', '$1', $first);
// Then get our first digit
$first = substr($first, 0, 1);
```

I essentially repeated that but used `strrpos` to do the check in reverse to get the last digit.

Looking back, I have a mess of code, but it works. For a bit of fun, that's okay.

I'm looking forward to tomorrow's challenge.


My full responses are found on [GitHub](https://github.com/benyafai/adventofcode/tree/main/2023/01)