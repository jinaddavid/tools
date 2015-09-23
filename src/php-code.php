<?php

/**
 * Allows validating and running PHP code.
 */
class PhpCode
{
  /**
   * Run the provided code intercepting PHP errors.
   *
   * If error-handling code is not supplied, an ErrorException will be thrown in the caller's context.
   *
   * @param callable $wrappedCode  Code to be executed wrapped by error catching code.
   * @param callable $errorHandler Optional error-handling code.
   * @param bool     $reset        True if the error status should be cleared so that Laravel does not intercept the
   *                               previous error.
   * @return mixed   The return value from the callable argument.
   *
   * @throws ErrorException
   * @throws Exception
   */
  static function catchErrorsOn ($wrappedCode, $errorHandler = null, $reset = true)
  {
    $prevHandler = set_error_handler (function ($errno, $errstr, $errfile, $errline) {
      if (!error_reporting ())
        return false;
      throw new ErrorException ($errstr, $errno, 0, $errfile, $errline);
    });

    try {
      // Run the caller-supplied code.
      $r = $wrappedCode();
      // Restore the previous error handler.
      set_error_handler ($prevHandler);

      return $r;
    } catch (Exception $e) {
      // Intercept the error that will be triggered below.
      set_error_handler (function () {
        // Force error_get_last() to be set.
        return false;
      });
      // Clear the current error message so that the framework will not intercept the previous error.
      if ($reset)
        trigger_error ("");
      // Restore the previous error handler.
      set_error_handler ($prevHandler);

      // Handle the error.
      if (isset($errorHandler))
        return $errorHandler($e);

      throw $e;
    }
  }

  /**
   * Loads and runs a PHP file, searching for it on the include path, stripping the BOM (if one is present) and throwing
   * catchable exceptions instead of fatal errors.
   * @param string $filename
   * @return mixed|null The value returned from the executed code, or `null` if no `return` is called.
   */
  static function exec ($filename)
  {
    $code = loadFile ($filename);
    if (!$code) throw new RuntimeException ("Can't load file $filename", 1);
    if (!self::validate ($code, $out))
      throw new RuntimeException ("Failed executing file $filename.\n\n$out", 2);
    return self::catchErrorsOn (function () use ($code) { self::run ($code); });
  }

  /**
   * Runs PHP code. It supports code either beginning with `<?php` or not but, if `<?php` is present, it must be the
   * first thing on the code string, excluding white space.
   * @param string $_code The source code.
   * @return mixed
   */
  static function run ($_code)
  {
    $_code = ltrim ($_code);
    if (substr ($_code, 0, 5) == '<?php')
      return eval(substr ($_code, 5));
    return eval($_code);
  }

  /**
   * Checks if the given PHP source code is syntactically valid.
   * @param string $code
   * @param int    $output If specified, captures the error moessage.
   * @return bool `true` if the code is syntatically correct.
   */
  static function validate ($code, &$output = 0)
  {
    $b = 0;
    foreach (token_get_all ($code) as $token)
      if ('{' == $token)
        ++$b;
      elseif ('}' == $token)
        --$b;
    if ($b)
      return false; // Unbalanced braces would break the eval below
    ob_start (); // Catch potential parse error messages
    $code = eval('if(0){' . $code . '}'); // Put $code in a dead code sandbox to prevent its execution
    if ($output != 0)
      $output = ob_get_clean ();
    else
      ob_end_clean ();
    return false !== $code;
  }

  /**
   * Checks if a given PHP expression is syntactically valid.
   * @param string $exp
   * @return boolean
   */
  static function validateExpression ($exp)
  {
    return eval ("return true;return $exp;");
  }

}