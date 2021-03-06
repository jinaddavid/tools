<?php
/*
 * A library that complements PHP's built-in string manipulation functions.
 */

if (!function_exists ('mb_chr')) {
  /**
   * Polyfill for the mb_chr function.
   *
   * @param int    $ord
   * @param string $encoding
   * @return string
   */
  function mb_chr ($ord, $encoding = 'UTF-8')
  {
    if ($encoding === 'UCS-4BE') {
      return pack ("N", $ord);
    }
    else {
      return mb_convert_encoding (mb_chr ($ord, 'UCS-4BE'), $encoding, 'UCS-4BE');
    }
  }
}

if (!function_exists ('mb_ord')) {
  /**
   * Polyfill for the mb_ord function.
   *
   * @param string $char
   * @param string $encoding
   * @return int
   */
  function mb_ord ($char, $encoding = 'UTF-8')
  {
    if ($encoding === 'UCS-4BE') {
      list(, $ord) = (strlen ($char) === 4) ? @unpack ('N', $char) : @unpack ('n', $char);

      return $ord;
    }
    else {
      return mb_ord (mb_convert_encoding ($char, 'UCS-4BE', $encoding), 'UCS-4BE');
    }
  }
}

if (!function_exists ('mb_str_pad')) {
  /**
   * Pads an unicode string to a certain length with another string.
   *
   * <p>Note: this provides the mb_str_pad that is missing from the mbstring module.
   *
   * @param string $str
   * @param int    $pad_len
   * @param string $pad_str
   * @param int    $dir
   * @param string $encoding
   *
   * @return null|string
   */
  function mb_str_pad ($str, $pad_len, $pad_str = ' ', $dir = STR_PAD_RIGHT, $encoding = 'UTF-8')
  {
    mb_internal_encoding ($encoding);
    $str_len     = mb_strlen ($str);
    $pad_str_len = mb_strlen ($pad_str);
    if (!$pad_len || !$pad_str_len || $pad_len <= $str_len) {
      return $str;
    }
    $result = null;
    if ($dir == STR_PAD_BOTH) {
      $length = ($pad_len - $str_len) / 2;
      $repeat = ceil ($length / $pad_str_len);
      $result = mb_substr (str_repeat ($pad_str, $repeat), 0, floor ($length))
                . $str
                . mb_substr (str_repeat ($pad_str, $repeat), 0, ceil ($length));
    }
    else {
      $repeat = ceil(($pad_len - $str_len) / $pad_str_len);
      $mod = ($pad_len - $str_len) % $pad_str_len;
      if ($dir == STR_PAD_RIGHT) {
        $result = str_repeat ($pad_str, $repeat);
        $result = $str . mb_substr ($result, $mod);
      }
      else if ($dir == STR_PAD_LEFT) {
        $result = str_repeat ($pad_str, $repeat);
        $result = mb_substr ($result, $mod) . $str;
      }
    }
    return $result;
  }
}

/**
 * Checks if a string begins with a given substring.
 *
 * @param string $str
 * @param string $substr
 * @return bool
 */
function str_beginsWith ($str, $substr)
{
  return substr ($str, 0, strlen ($substr)) == $substr;
}

/**
 * Checks if a string ends with a given substring.
 *
 * @param string $str
 * @param string $substr
 * @return bool
 */
function str_endsWith ($str, $substr)
{
  return substr ($str, -strlen ($substr)) == $substr;
}

/**
 * Truncates a string to a certain length and appends ellipsis to it.
 *
 * @param string $text
 * @param int    $limit
 * @param string $ending
 *
 * @return string
 */
function str_truncate ($text, $limit, $ending = '...')
{
  if (strlen ($text) > $limit) {
    $text = strip_tags ($text);
    $text = substr ($text, 0, $limit);
    $text = substr ($text, 0, -(strlen (strrchr ($text, ' '))));
    $text = $text . $ending;
  }

  return $text;
}

/**
 * Limits a string to a certain length by imploding the middle part of it.
 *
 * @param string $text
 * @param int    $limit
 * @param string $more Symbol that represents the removed part of the original string.
 *
 * @return string
 */
function str_cut ($text, $limit, $more = '...')
{
  if (strlen ($text) > $limit) {
    $chars = floor (($limit - strlen ($more)) / 2);
    $p     = strpos ($text, ' ', $chars) + 1;
    $d     = $p < 1 ? 0 : $p - $chars;

    return substr ($text, 0, $chars + $d) . $more . substr ($text, -$chars + $d);
  }

  return $text;
}

/**
 * Encodes a string to be outputted as a delimited string to a javascript block, in an HTML page.
 * Newlines and quotes that match the delimiters are escaped.
 *
 * > Ex. outputting an arbitrary string to an alert javascfript call:
 * ```
 * echo '<div onclick="alert(' . str_encodeJavasciptStr($text, "'") . ')">click me</div>';
 * ```
 *
 * @param string $str   The string to be encoded.
 * @param string $delim The delimiter used to enclose the javascript string (either " or ').
 *
 * @return string
 */
function str_encodeJavasciptStr ($str, $delim = '"')
{
  return $delim . htmlspecialchars (str_replace ($delim, '\\' . $delim, str_replace ("\n", '\\n', $str))) . $delim;
}

/**
 * Converts an hyphenated compound word into a camel-cased form.
 *
 * Ex: `my-long-name => myLongName` or `my_long_name => myLongName`
 *
 * @param string $name
 * @param bool   $ucfirst   When `true` the first letter is capitalized, otherwhise it is lower cased.
 * @param string $delimiter [optional] The character that is considered to be the 'hyphen'.
 * @return string
 */
function str_dehyphenate ($name, $ucfirst = false, $delimiter = '-')
{
  $s = str_replace ($delimiter, '', ucwords ($name, $delimiter));
  return $ucfirst ? $s : lcfirst ($s);
}

/**
 * Converts a string to camel cased form.
 *
 * @param string $name
 * @param bool   $ucfirst When `true` the first letter is capitalized, otherwhise it is lower cased.
 * @return string
 */
function str_camelize ($name, $ucfirst = false)
{
  $s = str_replace (' ', '', ucwords ($name));
  return $ucfirst ? $s : lcfirst ($s);
}

/**
 * Converts a string from camel cased form to some symbol-delimited list of words.
 *
 * @param string $name
 * @param bool   $ucwords   [optional] When true, each word is capitalized, otherwise it's "de-capitalized".
 * @param string $delimiter [optional] Joins words with this symbol. Defaults to space.
 * @return string
 */
function str_decamelize ($name, $ucwords = false, $delimiter = ' ')
{
  $w = preg_split ('/(?<!^)(?=[A-Z])|(?<!\d)(?=\d)/', $name);
  return implode ($delimiter, $ucwords ? array_map ('ucfirst', $w) : array_map ('lcfirst', $w));
}

/**
 * Lowercase the first character of each word in a string.
 *
 * @param string $str
 * @param string $delimiter The character used for delimiting words.
 * @return string
 */
function lcwords ($str, $delimiter = ' ')
{
  return implode ($delimiter, array_map ('lcfirst', explode ($delimiter, $str)));
}

function trimText ($text, $maxSize, $marker = ' (...)')
{
  if (strlen ($text) <= $maxSize)
    return $text;
  $a = explode (' ', substr ($text, 0, $maxSize));
  array_pop ($a);

  return join (' ', $a) . $marker;
}

function trimHTMLText ($text, $maxSize, $marker = '')
{
  if (mb_strlen ($text) <= $maxSize)
    return $text;
  $text = mb_substr ($text, 0, $maxSize);
  $a    = mb_strrpos ($text, '>');
  $b    = mb_strrpos ($text, '<');
  if ($b !== false && ($a === false || $a < $b))
    $text = mb_substr ($text, 0, $b);
  $a = mb_split ('/ /', $text);
  array_pop ($a);
  $text = join (' ', $a) . $marker;
  $tags = [];
  if (preg_match_all ('#<.*?>#u', $text, $matches)) {
    foreach ($matches[0] as $match)
      if (mb_substr ($match, 1, 1) == '/')
        array_pop ($tags);
      else if (mb_substr ($match, -2, 1) != '/')
        array_push ($tags, trim (mb_substr ($match, 1, mb_strlen ($match) - 2)));
    $tags = array_reverse ($tags);
    foreach ($tags as $tag) {
      $a = mb_strpos ($tag, ' ');
      if ($a)
        $tag = mb_substr ($tag, 0, $a);
      $text .= "</$tag>";
    }
  }

  return $text;
}

function strJoin ($s1, $s2, $delimiter)
{
  return strlen ($s1) && strlen ($s2) ? $s1 . $delimiter . $s2 : (strlen ($s1) ? $s1 : $s2);
}

/**
 * Performs padding on strings having embedded tags.
 *
 * This is specially useful when used with color-tagged strings meant for terminal output.
 * > Ex: `"<color-name>text</color-name>"`
 *
 * @param string $str
 * @param int    $width The desired minimum width, in characters.
 * @param int    $align One of the STR_PAD_XXX constants.
 * @param string $pad   The paddind character(s).
 * @return string
 */
function taggedStrPad ($str, $width, $align = STR_PAD_RIGHT, $pad = ' ')
{
  $w    = taggedStrLen ($str);
  $rawW = mb_strlen ($str);
  $d    = $rawW - $w;

  return mb_str_pad ($str, $width + $d, $pad, $align);
}

/**
 * Performs cropping on strings having embedded tags.
 *
 * This is specially useful when used with color-tagged strings meant for terminal output.
 * > Ex: `"<color-name>text</color-name>"`
 *
 * @param string $str
 * @param int    $width  The desired minimum width, in characters.
 * @param string $marker The overflow marker.
 * @return string
 */
function taggedStrCrop ($str, $width, $marker = '')
{
  $w = taggedStrLen ($str);
  if ($w <= $width)
    return $str;

  $o       = '';
  $tags    = [];
  $curLen  = 0;
  $markLen = mb_strlen ($marker, 'UTF-8');
  while (strlen ($str)) {
    if (!preg_match ('/<(.*?)>/u', $str, $m, PREG_OFFSET_CAPTURE))
      return $o . mb_substr ($str, 0, $width - $curLen - $markLen) . $marker;
    list ($tag, $ofs) = $m[0];
    $tagName = $m[1][0];
    $seg     = mb_substr ($str, 0, $ofs);
    $str     = mb_substr ($str, $ofs + mb_strlen ($tag, 'UTF-8'));
    $segLen  = mb_strlen ($seg, 'UTF-8');
    $curLen += $segLen;
    if ($curLen >= $width) {
      $o .= mb_substr ($seg, 0, $width - $curLen - $markLen) . $marker;
      break;
    }
    else $o .= $seg;
    if ($tag[1] == '/')
      array_pop ($tags);
    else $tags[] = $tagName;
    $o .= "$tag";
  }
  while ($tags)
    $o .= '</' . array_pop ($tags) . '>';
  return $o;
}

/**
 * Returns the true length of strings having embedded color tags.
 *
 * This is specially useful when used with color-tagged strings meant for terminal output.
 * > Ex: `"<color-name>text</color-name>"`
 *
 * @param string $str
 * @return int The string's length, in characters.
 */
function taggedStrLen ($str)
{
  return mb_strlen (preg_replace ('/<[^>]*>/u', '', $str));
}

/**
 * Extracts a substring from a string using a search pattern, returning a fixed-length array with the match and the
 * capture groups.
 *
 * @param string $source         The string from where to match a pattern.
 * @param string $pattern        A regular expression for selecting what text to match.
 * @param int    $groups         [optional] How many groups to return, even if not all optional groups are present.
 *                               The returned array will alwaus contain n+1 entries: the total match and the capture
 *                               groups.
 * @param bool   $falseOnNoMatch If true, when no match is found, false is returned instead of an array with empty
 *                               entries.
 * @param mixed  $emptyValue     The value with which to pad the returned array.
 * @return array|false The match, which consists of the total matched string and each of the captured groups.
 */
function str_match ($source, $pattern, $groups = 0, $falseOnNoMatch = false, $emptyValue = '')
{
  if (!preg_match ($pattern, $source, $m))
    return $falseOnNoMatch ? false : array_fill (0, $groups + 1, $emptyValue);
  return array_pad ($m, $groups + 1, $emptyValue);
}

/**
 * Extracts a substring from a string using a search pattern, removing the match from the original string and returning
 * it, or the first capture group, if one is defined.
 *
 * @param string $source  The string from where to extract a substring.
 * @param string $pattern A regular expression for selecting what text to extract.
 * @return string The extracted text, or '' if nothing matched.
 */
function str_extract (&$source, $pattern)
{
  $out    = '';
  $source = preg_replace_callback ($pattern, function ($m) use (&$out) {
    $out = count ($m) > 1 ? $m[1] : $m[0];
    return '';
  }, $source);
  return $out;
}

/**
 * Extracts a substring from the beginning of string using a search pattern for matching a delimitir sequence,
 * returning both the extracted segment and the remaining string.
 *
 * <p>Empty matches are skipped until a non-empty match is found.
 *
 * @param string $source           The string from where to extract a substring.
 * @param string $delimiterPattern A regular expression for selecting where to split. Note that everything that is
 *                                 matched by the pattern is stripped from both results.
 * @return string[] The extracted text and the remaining string. It always returns an array with 2 elements.
 */
function str_extractSegment ($source, $delimiterPattern)
{
  return array_merge (preg_split ($delimiterPattern, $source, 2, PREG_SPLIT_NO_EMPTY), ['', '']);
}

/**
 * Returns the first `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to extract file path segments (delimited by `'/'`).
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to retrieve.
 * @return string
 *
 * @see str_splitGetFirst which is similar, but returns an array.
 */
function str_segmentsFirst ($str, $delimiter, $count = 1)
{
  $p = -1;
  while ($count-- && $p !== false)
    $p = strpos ($str, $delimiter, $p + 1);
  if ($p === false) return $str;
  return substr ($str, 0, $p);
}

/**
 * Returns the last `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to extract file path segments (delimited by `'/'`) or to get a file extension.
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to retrieve.
 * @return string
 *
 * @see str_splitGetLast which is similar, but returns an array.
 */
function str_segmentsLast ($str, $delimiter, $count = 1)
{
  $p = 0;
  while ($count-- && $p !== false)
    $p = strrpos ($str, $delimiter, -$p - 1);
  if ($p === false) return $str;
  return substr ($str, $p + 1);
}

/**
 * Removes the first `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to remove segments of a file path.
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to remove.
 * @return string
 *
 * @see str_splitStripFirst which is similar, but returns an array.
 */
function str_segmentsStripFirst ($str, $delimiter, $count = 1)
{
  $p = -1;
  while ($count-- && $p !== false)
    $p = strpos ($str, $delimiter, $p + 1);
  if ($p === false) return $str;
  return substr ($str, $p + 1);
}

/**
 * Removes the last `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to remove an extension from a file path.
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to remove.
 * @return string
 *
 * @see str_splitStripLast which is similar, but returns an array.
 */
function str_segmentsStripLast ($str, $delimiter, $count = 1)
{
  $p = 0;
  while ($count-- && $p !== false)
    $p = strrpos ($str, $delimiter, -$p - 1);
  if ($p === false) return $str;
  return substr ($str, 0, $p);
}

/**
 * Returns the first `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to extract file path segments (delimited by `'/'`).
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to retrieve.
 * @return string[]
 *
 * @see str_segmentsFirst which is similar, but returns a string.
 */
function str_splitGetFirst ($str, $delimiter, $count = 1)
{
  return array_slice (explode ($delimiter, $str, $count + 1), 0, $count);
}

/**
 * Returns the last `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to extract file path segments (delimited by `'/'`) or to get a file extension.
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to retrieve.
 * @return string[]
 *
 * @see str_segmentsLast which is similar, but returns a string.
 */
function str_splitGetLast ($str, $delimiter, $count = 1)
{
  return array_slice (explode ($delimiter, $str), -$count);
}

/**
 * Removes the first `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to remove segments of a file path.
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to remove.
 * @return string[]
 *
 * @see str_segmentsStripFirst which is similar, but returns a string.
 */
function str_splitStripFirst ($str, $delimiter, $count = 1)
{
  return array_slice (explode ($delimiter, $str), $count);
}

/**
 * Removes the last `$count` segments of a string segmented by a given delimiter.
 *
 * > <p>**Ex:** you can use this to remove an extension from a file path.
 *
 * @param string $str
 * @param string $delimiter The segment delimiter to search for (ex: '/').
 * @param int    $count     How many segments to remove.
 * @return string[]
 *
 * @see str_segmentsStripLast which is similar, but returns a string.
 */
function str_splitStripLast ($str, $delimiter, $count = 1)
{
  return explode ($delimiter, $str, -$count);
}

/**
 * Finds the position of the first occurrence of a pattern in a given string.
 *
 * @param string $str     The string where to search on.
 * @param string $pattern A regular expression.
 * @param int    $from    The position where the search begins, counted from the beginning of the current string.
 * @param string $match   [optional] If a variable is specified, it will be set to the matched substring.
 * @return bool|int false if no match was found.
 */
function str_search ($str, $pattern, $from = 0, &$match = null)
{
  if (preg_match ($pattern, $str, $m, PREG_OFFSET_CAPTURE)) {
    list ($match, $ofs) = $m[0];
    return $ofs;
  }
  return false;
}

/**
 * Performs a simple english pluralization of an "x thing(s)" phrase.
 *
 * @param number $num
 * @param string $thing
 * @return string
 */
function simplePluralize ($num, $thing)
{
  return sprintf ('%s%s', $thing, $num == 1 ? '' : 's');
}

/**
 * Indents a (possibly multiline) string.
 *
 * @param string $str
 * @param int    $level  The indentation level; it will be multiplied by 2.
 * @param string $indent A pattern to be output at the start of each line, repeated $level times.
 * @return string
 */
function str_indent ($str, $level = 1, $indent = '  ')
{
  return preg_replace ('/^/m', str_repeat ($indent, $level), $str);
}
