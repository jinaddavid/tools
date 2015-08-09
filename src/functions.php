<?php

/**
 * Transforms a callable reference into a closure, with optional pre-bound and/or post-bound arguments.
 *
 * The closure can be used to call the original reference via `$x()` syntax.
 *
 * @param callable $fn       A function reference, in the form of:
 *                           <ul>
 *                           <li> a Closure instance,
 *                           <li> a function name string,
 *                           <li> a "class::method" string, or
 *                           <li> an array of (className,methodName).
 *                           <li> an array of (classInstance,methodName).
 *                           </ul>
 * @param array    $append   If specified, these arguments will be appended to the target function's arguments on each
 *                           call.
 *                           <p>Note: `$append` precedes `$prepend` because this is the most common case.
 * @param array    $prepend  If specified, these arguments will be prepended to the target function's arguments on each
 *                           call.
 * @return Closure
 */
function fn (callable $fn, array $append = [], array $prepend = [])
{
  if (func_num_args () == 1)
    return function () use ($fn) {
      return call_user_func_array ($fn, func_get_args ());
    };
  return function () use ($fn, $prepend, $append) {
    return call_user_func_array ($fn, array_merge ($prepend, func_get_args (), $append));
  };
}

/**
 * Compiles and returns a lambda function defined by the given string expression.
 *
 * The expression is compiled only once, further calls to this function with the same argument will return a cached
 * instance.
 * @param string $exp An expression with the syntax: "$arg1,$arg2,... => php_expression".
 *                    <p>The string must be delimited with single quotes.
 *                    <p>Ex:
 *                    <code>  f ('$x => $x+1')</code>
 *                    <code>  f ('$a, callable $b => $a + $b()')</code>
 * @return Closure
 */
function f ($exp)
{
  static $cache = [];
  if (isset($cache[$exp]))
    return $cache[$exp];
  list ($a, $f) = explode ('=>', $exp, 2);

  return $cache[$exp] = create_function ($a, "return $f;");
}

/**
 * Returns a function that, when invoked, returns the given value.
 *
 * @param mixed $i
 *
 * @return callable
 */
function wrap ($i)
{
  return function () use ($i) {
    return $i;
  };
}

/**
 * Wraps the given function with a caching decorator.
 * The original function will be invoked only once, on the first call.
 * Subsequent calls return the cached value.
 *
 * @param callable $fn
 *
 * @return callable
 */
function cache ($fn)
{
  $v = null;
  return function () use ($fn, &$v) {
    return isset ($v) ? $v : $v = call_user_func ($fn);
  };
}

/**
 * Returns a function that returns the input argument unmodified.
 * @return Closure
 */
function identity ()
{
  return function ($a) { return $a; };
}
