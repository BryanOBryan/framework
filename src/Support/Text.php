<?php
namespace Kaliba\Support;

use InvalidArgumentException;

/**
 * This class is partly based on the CakePHP Text class and CodeIgniter String class
 * 
 * Text handling methods.
 *
 */
class Text{

  
    /**
     * Tokenizes a string using $separator, ignoring any instance of $separator that appears between
     * $leftBound and $rightBound.
     *
     * @param string $data The data to tokenize.
     * @param string $separator The token to split the data on.
     * @param string $leftBound The left boundary to ignore separators in.
     * @param string $rightBound The right boundary to ignore separators in.
     * @return mixed Array of tokens in $data or original input if empty.
     */
    public static function tokenize($data, $separator = ',', $leftBound = '(', $rightBound = ')')
    {
        if (empty($data)) {
            return [];
        }

        $depth = 0;
        $offset = 0;
        $buffer = '';
        $results = [];
        $length = mb_strlen($data);
        $open = false;

        while ($offset <= $length) {
            $tmpOffset = -1;
            $offsets = [
                mb_strpos($data, $separator, $offset),
                mb_strpos($data, $leftBound, $offset),
                mb_strpos($data, $rightBound, $offset)
            ];
            for ($i = 0; $i < 3; $i++) {
                if ($offsets[$i] !== false && ($offsets[$i] < $tmpOffset || $tmpOffset == -1)) {
                    $tmpOffset = $offsets[$i];
                }
            }
            if ($tmpOffset !== -1) {
                $buffer .= mb_substr($data, $offset, ($tmpOffset - $offset));
                $char = mb_substr($data, $tmpOffset, 1);
                if (!$depth && $char === $separator) {
                    $results[] = $buffer;
                    $buffer = '';
                } else {
                    $buffer .= $char;
                }
                if ($leftBound !== $rightBound) {
                    if ($char === $leftBound) {
                        $depth++;
                    }
                    if ($char === $rightBound) {
                        $depth--;
                    }
                } else {
                    if ($char === $leftBound) {
                        if (!$open) {
                            $depth++;
                            $open = true;
                        } else {
                            $depth--;
                        }
                    }
                }
                $offset = ++$tmpOffset;
            } else {
                $results[] = $buffer . mb_substr($data, $offset);
                $offset = $length + 1;
            }
        }
        if (empty($results) && !empty($buffer)) {
            $results[] = $buffer;
        }

        if (!empty($results)) {
            return array_map('trim', $results);
        }

        return [];
    }

    /**
     * Replaces variable placeholders inside a $str with any given $data. Each key in the $data array
     * corresponds to a variable placeholder name in $str.
     * Example:
     * ```
     * Text::insert(':name is :age years old.', ['name' => 'Bob', '65']);
     * ```
     * Returns: Bob is 65 years old.
     *
     * Available $options are:
     *
     * - before: The character or string in front of the name of the variable placeholder (Defaults to `:`)
     * - after: The character or string after the name of the variable placeholder (Defaults to null)
     * - escape: The character or string used to escape the before character / string (Defaults to `\`)
     * - format: A regex to use for matching variable placeholders. Default is: `/(?<!\\)\:%s/`
     *   (Overwrites before, after, breaks escape / clean)
     * - clean: A boolean or array with instructions for Text::cleanInsert
     *
     * @param string $str A string containing variable placeholders
     * @param array $data A key => val array where each key stands for a placeholder variable name
     *     to be replaced with val
     * @param array $options An array of options, see description above
     * @return string
     */
    public static function insert($str, $data, array $options = [])
    {
        $defaults = [
            'before' => ':', 'after' => null, 'escape' => '\\', 'format' => null, 'clean' => false
        ];
        $options += $defaults;
        $format = $options['format'];
        $data = (array)$data;
        if (empty($data)) {
            return ($options['clean']) ? static::cleanInsert($str, $options) : $str;
        }

        if (!isset($format)) {
            $format = sprintf(
                '/(?<!%s)%s%%s%s/',
                preg_quote($options['escape'], '/'),
                str_replace('%', '%%', preg_quote($options['before'], '/')),
                str_replace('%', '%%', preg_quote($options['after'], '/'))
            );
        }

        if (strpos($str, '?') !== false && is_numeric(key($data))) {
            $offset = 0;
            while (($pos = strpos($str, '?', $offset)) !== false) {
                $val = array_shift($data);
                $offset = $pos + strlen($val);
                $str = substr_replace($str, $val, $pos, 1);
            }
            return ($options['clean']) ? static::cleanInsert($str, $options) : $str;
        }

        asort($data);

        $dataKeys = array_keys($data);
        $hashKeys = array_map('crc32', $dataKeys);
        $tempData = array_combine($dataKeys, $hashKeys);
        krsort($tempData);

        foreach ($tempData as $key => $hashVal) {
            $key = sprintf($format, preg_quote($key, '/'));
            $str = preg_replace($key, $hashVal, $str);
        }
        $dataReplacements = array_combine($hashKeys, array_values($data));
        foreach ($dataReplacements as $tmpHash => $tmpValue) {
            $tmpValue = (is_array($tmpValue)) ? '' : $tmpValue;
            $str = str_replace($tmpHash, $tmpValue, $str);
        }

        if (!isset($options['format']) && isset($options['before'])) {
            $str = str_replace($options['escape'] . $options['before'], $options['before'], $str);
        }
        return ($options['clean']) ? static::cleanInsert($str, $options) : $str;
    }

    /**
     * Cleans up a Text::insert() formatted string with given $options depending on the 'clean' key in
     * $options. The default method used is text but html is also available. The goal of this function
     * is to replace all whitespace and unneeded markup around placeholders that did not get replaced
     * by Text::insert().
     *
     * @param string $str String to clean.
     * @param array $options Options list.
     * @return string
     * @see \Cake\Support\Text::insert()
     */
    public static function cleanInsert($str, array $options)
    {
        $clean = $options['clean'];
        if (!$clean) {
            return $str;
        }
        if ($clean === true) {
            $clean = ['method' => 'text'];
        }
        if (!is_array($clean)) {
            $clean = ['method' => $options['clean']];
        }
        switch ($clean['method']) {
            case 'html':
                $clean += [
                    'word' => '[\w,.]+',
                    'andText' => true,
                    'replacement' => '',
                ];
                $kleenex = sprintf(
                    '/[\s]*[a-z]+=(")(%s%s%s[\s]*)+\\1/i',
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/')
                );
                $str = preg_replace($kleenex, $clean['replacement'], $str);
                if ($clean['andText']) {
                    $options['clean'] = ['method' => 'text'];
                    $str = static::cleanInsert($str, $options);
                }
                break;
            case 'text':
                $clean += [
                    'word' => '[\w,.]+',
                    'gap' => '[\s]*(?:(?:and|or)[\s]*)?',
                    'replacement' => '',
                ];

                $kleenex = sprintf(
                    '/(%s%s%s%s|%s%s%s%s)/',
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/'),
                    $clean['gap'],
                    $clean['gap'],
                    preg_quote($options['before'], '/'),
                    $clean['word'],
                    preg_quote($options['after'], '/')
                );
                $str = preg_replace($kleenex, $clean['replacement'], $str);
                break;
        }
        return $str;
    }

    /**
     * Wraps text to a specific width, can optionally wrap at word breaks.
     *
     * ### Options
     *
     * - `width` The width to wrap to. Defaults to 72.
     * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
     * - `indent` String to indent with. Defaults to null.
     * - `indentAt` 0 based index to start indenting at. Defaults to 0.
     *
     * @param string $text The text to format.
     * @param array|int $options Array of options to use, or an integer to wrap the text to.
     * @return string Formatted text.
     */
    public static function wrap($text, $options = [])
    {
        if (is_numeric($options)) {
            $options = ['width' => $options];
        }
        $options += ['width' => 72, 'wordWrap' => true, 'indent' => null, 'indentAt' => 0];
        if ($options['wordWrap']) {
            $wrapped = self::wordWrap($text, $options['width'], "\n");
        } else {
            $wrapped = trim(chunk_split($text, $options['width'] - 1, "\n"));
        }
        if (!empty($options['indent'])) {
            $chunks = explode("\n", $wrapped);
            for ($i = $options['indentAt'], $len = count($chunks); $i < $len; $i++) {
                $chunks[$i] = $options['indent'] . $chunks[$i];
            }
            $wrapped = implode("\n", $chunks);
        }
        return $wrapped;
    }

    /**
     * Wraps a complete block of text to a specific width, can optionally wrap
     * at word breaks.
     *
     * ### Options
     *
     * - `width` The width to wrap to. Defaults to 72.
     * - `wordWrap` Only wrap on words breaks (spaces) Defaults to true.
     * - `indent` String to indent with. Defaults to null.
     * - `indentAt` 0 based index to start indenting at. Defaults to 0.
     *
     * @param string $text The text to format.
     * @param array|int $options Array of options to use, or an integer to wrap the text to.
     * @return string Formatted text.
     */
    public static function wrapBlock($text, $options = [])
    {
        if (is_numeric($options)) {
            $options = ['width' => $options];
        }
        $options += ['width' => 72, 'wordWrap' => true, 'indent' => null, 'indentAt' => 0];

        if (!empty($options['indentAt']) && $options['indentAt'] === 0) {
            $indentLength = !empty($options['indent']) ? strlen($options['indent']) : 0;
            $options['width'] = $options['width'] - $indentLength;
            return self::wrap($text, $options);
        }

        $wrapped = self::wrap($text, $options);

        if (!empty($options['indent'])) {
            $indentationLength = mb_strlen($options['indent']);
            $chunks = explode("\n", $wrapped);
            $count = count($chunks);
            if ($count < 2) {
                return $wrapped;
            }
            $toRewrap = '';
            for ($i = $options['indentAt']; $i < $count; $i++) {
                $toRewrap .= mb_substr($chunks[$i], $indentationLength) . ' ';
                unset($chunks[$i]);
            }
            $options['width'] -= $indentationLength;
            $options['indentAt'] = 0;
            $rewrapped = self::wrap($toRewrap, $options);
            $newChunks = explode("\n", $rewrapped);

            $chunks = array_merge($chunks, $newChunks);
            $wrapped = implode("\n", $chunks);
        }
        return $wrapped;
    }

    /**
     * Unicode and newline aware version of wordwrap.
     *
     * @param string $text The text to format.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string Formatted text.
     */
    public static function wordWrap($text, $width = 72, $break = "\n", $cut = false)
    {
        $paragraphs = explode($break, $text);
        foreach ($paragraphs as &$paragraph) {
            $paragraph = static::_wordWrap($paragraph, $width, $break, $cut);
        }
        return implode($break, $paragraphs);
    }

    /**
     * Unicode aware version of wordwrap as helper method.
     *
     * @param string $text The text to format.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string Formatted text.
     */
    protected static function _wordWrap($text, $width = 72, $break = "\n", $cut = false)
    {
        if ($cut) {
            $parts = [];
            while (mb_strlen($text) > 0) {
                $part = mb_substr($text, 0, $width);
                $parts[] = trim($part);
                $text = trim(mb_substr($text, mb_strlen($part)));
            }
            return implode($break, $parts);
        }

        $parts = [];
        while (mb_strlen($text) > 0) {
            if ($width >= mb_strlen($text)) {
                $parts[] = trim($text);
                break;
            }

            $part = mb_substr($text, 0, $width);
            $nextChar = mb_substr($text, $width, 1);
            if ($nextChar !== ' ') {
                $breakAt = mb_strrpos($part, ' ');
                if ($breakAt === false) {
                    $breakAt = mb_strpos($text, ' ', $width);
                }
                if ($breakAt === false) {
                    $parts[] = trim($text);
                    break;
                }
                $part = mb_substr($text, 0, $breakAt);
            }

            $part = trim($part);
            $parts[] = $part;
            $text = trim(mb_substr($text, mb_strlen($part)));
        }

        return implode($break, $parts);
    }

    /**
     * Highlights a given phrase in a text. You can specify any expression in highlighter that
     * may include the \1 expression to include the $phrase found.
     *
     * ### Options:
     *
     * - `format` The piece of HTML with that the phrase will be highlighted
     * - `html` If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
     * - `regex` a custom regex rule that is used to match words, default is '|$tag|iu'
     *
     * @param string $text Text to search the phrase in.
     * @param string|array $phrase The phrase or phrases that will be searched.
     * @param array $options An array of HTML attributes and options.
     * @return string The highlighted text
     * 
     */
    public static function highlight($text, $phrase, array $options = [])
    {
        if (empty($phrase)) {
            return $text;
        }

        $defaults = [
            'format' => '<span class="highlight">\1</span>',
            'html' => false,
            'regex' => "|%s|iu"
        ];
        $options += $defaults;
        extract($options);

        if (is_array($phrase)) {
            $replace = [];
            $with = [];

            foreach ($phrase as $key => $segment) {
                $segment = '(' . preg_quote($segment, '|') . ')';
                if ($html) {
                    $segment = "(?![^<]+>)$segment(?![^<]+>)";
                }

                $with[] = (is_array($format)) ? $format[$key] : $format;
                $replace[] = sprintf($options['regex'], $segment);
            }

            return preg_replace($replace, $with, $text);
        }

        $phrase = '(' . preg_quote($phrase, '|') . ')';
        if ($html) {
            $phrase = "(?![^<]+>)$phrase(?![^<]+>)";
        }

        return preg_replace(sprintf($options['regex'], $phrase), $format, $text);
    }

    /**
     * Strips given text of all links (<a href=....).
     *
     * @param string $text Text
     * @return string The text without links
     */
    public static function stripLinks($text)
    {
        return preg_replace('|<a\s+[^>]+>|im', '', preg_replace('|<\/a>|im', '', $text));
    }

    /**
     * Truncates text starting from the end.
     *
     * Cuts a string to the length of $length and replaces the first characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as Beginning and prepended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of options.
     * @return string Trimmed string.
     */
    public static function tail($text, $length = 100, array $options = [])
    {
        $default = [
            'ellipsis' => '...', 'exact' => true
        ];
        $options += $default;
        extract($options);

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $truncate = mb_substr($text, mb_strlen($text) - $length + mb_strlen($ellipsis));
        if (!$exact) {
            $spacepos = mb_strpos($truncate, ' ');
            $truncate = $spacepos === false ? '' : trim(mb_substr($truncate, $spacepos));
        }

        return $ellipsis . $truncate;
    }

    /**
     * Truncates text.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with the ellipsis if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as ending and appended to the trimmed string
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags would be handled correctly
     *
     * @param string $text String to truncate.
     * @param int $length Length of returned string, including ellipsis.
     * @param array $options An array of HTML attributes and options.
     * @return string Trimmed string.
     * 
     */
    public static function truncate($text, $length = 100, array $options = [])
    {
        $default = [
            'ellipsis' => '...', 'exact' => true, 'html' => false
        ];
        if (!empty($options['html']) && strtolower(mb_internal_encoding()) === 'utf-8') {
            $default['ellipsis'] = "\xe2\x80\xa6";
        }
        $options += $default;
        extract($options);

        if ($html) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $totalLength = mb_strlen(strip_tags($ellipsis));
            $openTags = [];
            $truncate = '';

            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0, $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            }
            $truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if ($html) {
                $truncateCheck = mb_substr($truncate, 0, $spacepos);
                $lastOpenTag = mb_strrpos($truncateCheck, '<');
                $lastCloseTag = mb_strrpos($truncateCheck, '>');
                if ($lastOpenTag > $lastCloseTag) {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $lastTagMatches);
                    $lastTag = array_pop($lastTagMatches[0]);
                    $spacepos = mb_strrpos($truncate, $lastTag) + mb_strlen($lastTag);
                }
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                if (!empty($droppedTags)) {
                    if (!empty($openTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    } else {
                        foreach ($droppedTags as $closingTag) {
                            $openTags[] = $closingTag[1];
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);

            // If truncate still empty, then we don't need to count ellipsis in the cut.
            if (mb_strlen($truncate) === 0) {
                $truncate = mb_substr($text, 0, $length);
            }
        }

        $truncate .= $ellipsis;

        if ($html) {
            foreach ($openTags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

    /**
     * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side
     * determined by radius.
     *
     * @param string $text String to search the phrase in
     * @param string $phrase Phrase that will be searched for
     * @param int $radius The amount of characters that will be returned on each side of the founded phrase
     * @param string $ellipsis Ending that will be appended
     * @return string Modified string
     * 
     */
    public static function excerpt($text, $phrase, $radius = 100, $ellipsis = '...')
    {
        if (empty($text) || empty($phrase)) {
            return static::truncate($text, $radius * 2, ['ellipsis' => $ellipsis]);
        }

        $append = $prepend = $ellipsis;

        $phraseLen = mb_strlen($phrase);
        $textLen = mb_strlen($text);

        $pos = mb_strpos(mb_strtolower($text), mb_strtolower($phrase));
        if ($pos === false) {
            return mb_substr($text, 0, $radius) . $ellipsis;
        }

        $startPos = $pos - $radius;
        if ($startPos <= 0) {
            $startPos = 0;
            $prepend = '';
        }

        $endPos = $pos + $phraseLen + $radius;
        if ($endPos >= $textLen) {
            $endPos = $textLen;
            $append = '';
        }

        $excerpt = mb_substr($text, $startPos, $endPos - $startPos);
        $excerpt = $prepend . $excerpt . $append;

        return $excerpt;
    }

    /**
     * Creates a comma separated list where the last two items are joined with 'and', forming natural language.
     *
     * @param array $list The list to be joined.
     * @param string $and The word used to join the last and second last items together with. Defaults to 'and'.
     * @param string $separator The separator used to join all the other items together. Defaults to ', '.
     * @return string The glued together string.
     * 
     */
    public static function toList(array $list, $and = null, $separator = ', ')
    {
        if ($and === null) {
            $and = __d('cake', 'and');
        }
        if (count($list) > 1) {
            return implode($separator, array_slice($list, null, -1)) . ' ' . $and . ' ' . array_pop($list);
        }

        return array_pop($list);
    }

    /**
     * Check if the string contain multibyte characters
     *
     * @param string $string value to test
     * @return bool
     */
    public static function isMultibyte($string)
    {
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $value = ord(($string[$i]));
            if ($value > 128) {
                return true;
            }
        }
        return false;
    }

    /**
     * Converts a multibyte character string
     * to the decimal value of the character
     *
     * @param string $string String to convert.
     * @return array
     */
    public static function utf8($string)
    {
        $map = [];

        $values = [];
        $find = 1;
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            $value = ord($string[$i]);

            if ($value < 128) {
                $map[] = $value;
            } else {
                if (empty($values)) {
                    $find = ($value < 224) ? 2 : 3;
                }
                $values[] = $value;

                if (count($values) === $find) {
                    if ($find == 3) {
                        $map[] = (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64);
                    } else {
                        $map[] = (($values[0] % 32) * 64) + ($values[1] % 64);
                    }
                    $values = [];
                    $find = 1;
                }
            }
        }
        return $map;
    }

    /**
     * Converts the decimal value of a multibyte character string
     * to a string
     *
     * @param array $array Array array to convert
     * @return string
     */
    public static function ascii(array $array)
    {
        $ascii = '';

        foreach ($array as $utf8) {
            if ($utf8 < 128) {
                $ascii .= chr($utf8);
            } elseif ($utf8 < 2048) {
                $ascii .= chr(192 + (($utf8 - ($utf8 % 64)) / 64));
                $ascii .= chr(128 + ($utf8 % 64));
            } else {
                $ascii .= chr(224 + (($utf8 - ($utf8 % 4096)) / 4096));
                $ascii .= chr(128 + ((($utf8 % 4096) - ($utf8 % 64)) / 64));
                $ascii .= chr(128 + ($utf8 % 64));
            }
        }
        return $ascii;
    }

    /**
     * Converts filesize from human readable string to bytes
     *
     * @param string $size Size in human readable string like '5MB', '5M', '500B', '50kb' etc.
     * @param mixed $default Value to be returned when invalid size was used, for example 'Unknown type'
     * @return mixed Number of bytes as integer on success, `$default` on failure if not false
     * @throws \InvalidArgumentException On invalid Unit type.
     * 
     */
    public static function parseFileSize($size, $default = false)
    {
        if (ctype_digit($size)) {
            return (int)$size;
        }
        $size = strtoupper($size);

        $l = -2;
        $i = array_search(substr($size, -2), ['KB', 'MB', 'GB', 'TB', 'PB']);
        if ($i === false) {
            $l = -1;
            $i = array_search(substr($size, -1), ['K', 'M', 'G', 'T', 'P']);
        }
        if ($i !== false) {
            $size = substr($size, 0, $l);
            return $size * pow(1024, $i + 1);
        }

        if (substr($size, -1) === 'B' && ctype_digit(substr($size, 0, -1))) {
            $size = substr($size, 0, -1);
            return (int)$size;
        }

        if ($default !== false) {
            return $default;
        }
        throw new InvalidArgumentException('No unit type.');
    }
    
    /**
     * split the namespace from the classname.
     *
     * Supportly used like `list($namespace, $className) = namespaceSplit($class);`.
     *
     * @param string $class The full class name, ie `Cake\Core\App`.
     * @return array Array with 2 indexes. 0 => namespace, 1 => classname.
     */
    public static function sliptNameSpace($class)
    {
        $pos = strrpos($class, '\\');
        if ($pos === false) {
            return ['', $class];
        }
        return [substr($class, 0, $pos), substr($class, $pos + 1)];
    }
    
    /**
     * Strip Slashes
     *
     * Removes slashes contained in a string or in an array
     *
     * @param	mixed	string or array string to strip slashes from
     * @return	mixed	string or array
    */
    public static function stripSlashes($str)
    {
        if ( ! is_array($str))
        {
            return stripslashes($str);
        }

        foreach ($str as $key => $val)
        {
            $str[$key] = strip_slashes($val);
        }

        return $str;
    }
    
    /**
     * strip Quotes
     *
     * Removes single and double quotes from a string
     *
     * @param	string string to quote
     * @return	string
    */
    public static function stripQuotes($str)
    {
        return str_replace(array('"', "'"), '', $str);
    }
    
    /**
     * Quotes to Entities
     *
     * Converts single and double quotes to entities
     *
     * @param	string string to convert
     * @return	string
    */
    public static function quotesToEntities($str)
    {
        return str_replace(array("\'","\"","'",'"'), array("&#39;","&quot;","&#39;","&quot;"), $str);
    }
    
    /**
     * Reduce Double Slashes
     *
     * Converts double slashes in a string to a single slash,
     * except those found in http://
     *
     * http://www.some-site.com//index.php
     *
     * becomes:
     *
     * http://www.some-site.com/index.php
     *
     * @param	string
     * @return	string
     */
    public static function resolveDoubleSlashes($str)
    {
        return preg_replace('#(^|[^:])//+#', '\\1/', $str);
    }
    
    /**
     * Reduce Multiples
     *
     * Reduces multiple instances of a particular character.  Example:
     *
     * Fred, Bill,, Joe, Jimmy
     *
     * becomes:
     *
     * Fred, Bill, Joe, Jimmy
     *
     * @param	string
     * @param	string	the character you wish to reduce
     * @param	bool	TRUE/FALSE - whether to trim the character from the beginning/end
     * @return	string
     */
    public static function resolveMultiples($str, $character = ',', $trim = FALSE)
    {
        $str = preg_replace('#'.preg_quote($character, '#').'{2,}#', $character, $str);
        return ($trim === TRUE) ? trim($str, $character) : $str;
    }
    
    /**
     * Add's _1 to a string or increment the ending number to allow _2, _3, etc
     *
     * @param	string	required
     * @param	string	What should the duplicate number be appended with
     * @param	string	Which number should be used for the first dupe increment
     * @return	string
     */
    public static function incrementString($str, $separator = '_', $first = 1)
    {
        preg_match('/(.+)'.preg_quote($separator, '/').'([0-9]+)$/', $str, $match);
        return isset($match[2]) ? $match[1].$separator.($match[2] + 1) : $str.$separator.$first;
    }
    
    /**
     * High ASCII to Entities
     *
     * Converts high ASCII text and MS Word special characters to character entities
     *
     * @param	string	$str
     * @return	string
     */
    public static function asciiToEntities($str)
    {
        $out = '';
        for ($i = 0, $s = strlen($str) - 1, $count = 1, $temp = array(); $i <= $s; $i++)
        {
            $ordinal = ord($str[$i]);

            if ($ordinal < 128)
            {
                if (count($temp) === 1)
                {
                    $out .= '&#'.array_shift($temp).';';
                    $count = 1;
                }

                $out .= $str[$i];
            }
            else
            {
                if (count($temp) === 0)
                {
                    $count = ($ordinal < 224) ? 2 : 3;
                }

                $temp[] = $ordinal;

                if (count($temp) === $count)
                {
                    $number = ($count === 3)? (($temp[0] % 16) * 4096) + (($temp[1] % 64) * 64) + ($temp[2] % 64): (($temp[0] % 32) * 64) + ($temp[1] % 64);
                    $out .= '&#'.$number.';';
                    $count = 1;
                    $temp = array();
                }
                // If this is the last iteration, just output whatever we have
                elseif ($i === $s)
                {
                    $out .= '&#'.implode(';', $temp).';';
                }
            }
        }

        return $out;
    }
    
    /**
     * Entities to ASCII
     *
     * Converts character entities back to ASCII
     *
     * @param	string
     * @param	bool
     * @return	string
     */
    public static function entitiesToAscii($str, $all = TRUE)
    {
        if (preg_match_all('/\&#(\d+)\;/', $str, $matches)){
            for ($i = 0, $s = count($matches[0]); $i < $s; $i++){
                $digits = $matches[1][$i];
                $out = '';
                if ($digits < 128)
                {
                    $out .= chr($digits);

                }
                elseif($digits < 2048)
                {
                    $out .= chr(192 + (($digits - ($digits % 64)) / 64)).chr(128 + ($digits % 64));
                }
                else
                {
                    $out .= chr(224 + (($digits - ($digits % 4096)) / 4096)).chr(128 + ((($digits % 4096) - ($digits % 64)) / 64)).chr(128 + ($digits % 64));
                }

                $str = str_replace($matches[0][$i], $out, $str);
            }
        }

        if ($all)
        {
            return str_replace(
                    array('&amp;', '&lt;', '&gt;', '&quot;', '&apos;', '&#45;'),
                    array('&', '<', '>', '"', "'", '-'),
                    $str
            );
        }

        return $str;
    }
     
    /**
     * Word Censoring Function
     *
     * Supply a string and an array of disallowed words and any
     * matched words will be converted to #### or to the replacement
     * word you've submitted.
     *
     * @param	string	the text string
     * @param	string	the array of censored words
     * @param	string	the optional replacement value
     * @return	string
     */
    public static function wordCensor($str, $censored, $replacement = '')
    {
        if (!is_array($censored))
        {
            return $str;
        }

        $str = ' '.$str.' ';

        // \w, \b and a few others do not match on a unicode character
        // set for performance reasons. As a result words like über
        // will not match on a word boundary. Instead, we'll assume that
        // a bad word will be bookeneded by any of these characters.
        $delim = '[-_\'\"`(){}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

        foreach ($censored as $badword)
        {
            if ($replacement !== '')
            {
                $str = preg_replace("/({$delim})(".str_replace('\*', '\w*?', preg_quote($badword, '/')).")({$delim})/i", "\\1{$replacement}\\3", $str);
            }
            else
            {
                $str = preg_replace("/({$delim})(".str_replace('\*', '\w*?', preg_quote($badword, '/')).")({$delim})/ie", "'\\1'.str_repeat('#', strlen('\\2')).'\\3'", $str);
            }
        }

        return trim($str);
    }
    
    /**
     * Code Highlighter
     *
     * Colorizes code strings
     *
     * @param	string	the text string
     * @return	string
     */
    public static function highlightCode($str)
    {
        /* The highlight string function encodes and highlights
         * brackets so we need them to start raw.
         *
         * Also replace any existing PHP tags to temporary markers
         * so they don't accidentally break the string out of PHP,
         * and thus, thwart the highlighting.
         */
        $str = str_replace(
            array('&lt;', '&gt;', '<?', '?>', '<%', '%>', '\\', '</script>'),
            array('<', '>', 'phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'),
            $str
        );

        // The highlight_string function requires that the text be surrounded
        // by PHP tags, which we will remove later
        $str = highlight_string('<?php '.$str.' ?>', TRUE);

        // Remove our artificially added PHP, and the syntax highlighting that came with it
        $str = preg_replace(
            array(
                '/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i',
                '/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is',
                '/<span style="color: #[A-Z0-9]+"\><\/span>/i'
            ),
            array(
                '<span style="color: #$1">',
                "$1</span>\n</span>\n</code>",
                ''
            ),
            $str
        );

        // Replace our markers back to PHP tags.
        return str_replace(
            array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'),
            array('&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'),
            $str
        );
    }
    
    /**
     * Phrase Highlighter
     *
     * Highlights a phrase within a text string
     *
     * @param	string	$str		the text string
     * @param	string	$phrase		the phrase you'd like to highlight
     * @param	string	$tag_open	the opening tag to precede the phrase with
     * @param	string	$tag_close	the closing tag to end the phrase with
     * @return	string
     */
    public static function highlightPhrase($str, $phrase, $tag_open = '<mark>', $tag_close = '</mark>')
    {
        return ($str !== '' && $phrase !== '')
                ? preg_replace('/('.preg_quote($phrase, '/').')/i'.(UTF8_ENABLED ? 'u' : ''), $tag_open.'\\1'.$tag_close, $str)
                : $str;
    }
    
    /**
     * Ellipsize String
     *
     * This function will strip tags from a string, split it at its max_length and ellipsize
     *
     * @param	string	string to ellipsize
     * @param	int	max length of string
     * @param	mixed	int (1|0) or float, .5, .2, etc for position to split
     * @param	string	ellipsis ; Default '...'
     * @return	string	ellipsized string
     */
    public static function ellipsize($str, $max_length, $position = 1, $ellipsis = '&hellip;')
    {
        // Strip tags
        $str = trim(strip_tags($str));

        // Is the string long enough to ellipsize?
        if (mb_strlen($str) <= $max_length)
        {
            return $str;
        }

        $beg = mb_substr($str, 0, floor($max_length * $position));
        $position = ($position > 1) ? 1 : $position;

        if ($position === 1)
        {
            $end = mb_substr($str, 0, -($max_length - mb_strlen($beg)));
        }
        else
        {
            $end = mb_substr($str, -($max_length - mb_strlen($beg)));
        }

        return $beg.$ellipsis.$end;
    }
    
    /**
     * Add Slash
     *
     * Adds a slash to the end of a string:
     *
     * this/that/theother
     *
     * becomes:
     *
     * this/that/theother/
     *
     * @param	string string to add slash to
     * @return	string
     */
    public static function addSlash($str)
    {
          return rtrim($str, '/').'/';
    }
    
    /**
     * Trim Slashes
     *
     * Removes any leading/trailing slashes from a string:
     *
     * /this/that/theother/
     *
     * becomes:
     *
     * this/that/theother
     *
     * @param	string string to remove slash from
     * @return	string
     */
    public static function trimSlashes($str)
    {
        return trim($str, '/');
    }
    
    /**
     * Convenience method for htmlspecialchars.
     *
     * @param string|array|object $text Text to wrap through htmlspecialchars. Also works with arrays, and objects.
     * Arrays will be mapped and have all their elements escaped. Objects will be string cast if they
     * implement a `__toString` method. Otherwise the class name will be used.
     * @param bool $double Encode existing html entities.
     * @param string $charset Character set to use when escaping. Defaults to config value in `mb_internal_encoding()`
     * or 'UTF-8'.
     * @return string Wrapped text.
     */
    public static function htmlSpecialChars($text, $double = true, $charset = null)
    {
        if (is_string($text)) {
            //optimize for strings
        } elseif (is_array($text)) {
            $texts = [];
            foreach ($text as $k => $t) {
                $texts[$k] = h($t, $double, $charset);
            }
            return $texts;
        } elseif (is_object($text)) {
            if (method_exists($text, '__toString')) {
                $text = (string)$text;
            } else {
                $text = '(object)' . get_class($text);
            }
        } elseif (is_bool($text)) {
            return $text;
        }

        static $defaultCharset = false;
        if ($defaultCharset === false) {
            $defaultCharset = mb_internal_encoding();
            if ($defaultCharset === null) {
                $defaultCharset = 'UTF-8';
            }
        }
        if (is_string($double)) {
            $charset = $double;
        }
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, ($charset) ? $charset : $defaultCharset, $double);
    }
    
     /**
     * Splits a dot syntax plugin name into its plugin and class name.
     * If $name does not have a dot, then index 0 will be null.
     *
     * Supportly used like
     * ```
     * list($plugin, $name) = plugin_split($name);
     * ```
     *
     * @param string $name The name you want to plugin split.
     * @param bool $dotAppend Set to true if you want the plugin to have a '.' appended to it.
     * @param string $plugin Optional default plugin to use if no plugin is found. Defaults to null.
     * @return array Array with 2 indexes. 0 => plugin name, 1 => class name.
     */
    public static function splitPlugin($name, $dotAppend = false, $plugin = null)
    {
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name, 2);
            if ($dotAppend) {
                $parts[0] .= '.';
            }
            return $parts;
        }
        return [$plugin, $name];
    }

    /**
     * Word Limiter
     *
     * Limits a string to X number of words.
     *
     * @param	string
     * @param	int
     * @param	string	the end character. Usually an ellipsis
     * @return	string
     */
    public static function limitWord($str, $limit = 100, $end_char = '&#8230;')
    {
        if (trim($str) === '')
        {
            return $str;
        }

        preg_match('/^\s*+(?:\S++\s*+){1,'.(int) $limit.'}/', $str, $matches);

        if (strlen($str) === strlen($matches[0]))
        {
            $end_char = '';
        }

        return rtrim($matches[0]).$end_char;
    }
 
    /**
     * Strip Image Tags
     *
     * @param	string	$str to b stripped
     * @return	string
     */
    public static function stripImageTags($str)
    {
        return preg_replace(array('#<img[\s/]+.*?src\s*=\s*["\'](.+?)["\'].*?\>#', '#<img[\s/]+.*?src\s*=\s*(.+?).*?\>#'), '\\1', $str);
    }
    
    /**
     * Compares two strings using a constant-time algorithm.
     *
     * Note: This method will leak length information.
     *
     *
     * @param  string  $knownString
     * @param  string  $userInput
     * @return bool
     */
    public static function equals($knownString, $userInput)
    {
        return hash_equals($knownString, $userInput);
    }
    
    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $position = strpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param  string  $search
     * @param  string  $replace
     * @param  string  $subject
     * @return string
     */
    public static function replaceLast($search, $replace, $subject)
    {
        $position = strrpos($subject, $search);

        if ($position !== false) {
            return substr_replace($subject, $replace, $position, strlen($search));
        }

        return $subject;
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function upper($value)
    {
        return mb_strtoupper($value, 'UTF-8');
    }

    /**
     * Convert the given string to lower-case.
     *
     * @param  string  $value
     * @return string
     */
    public static function lower($value)
    {
        return mb_strtolower($value, 'UTF-8');
    }
    
    /**
     * Convert the given string to title case.
     *
     * @param  string  $value
     * @return string
     */
    public static function title($value)
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
    
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle != '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string ends with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function endsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ((string) $needle === substr($haystack, -strlen($needle))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cap a string with a single instance of a given value.
     *
     * @param  string  $value
     * @param  string  $cap
     * @return string
     */
    public static function finish($value, $cap)
    {
        $quoted = preg_quote($cap, '/');

        return preg_replace('/(?:'.$quoted.')+$/', '', $value).$cap;
    }

    /**
     * Determine if a given string matches a given pattern.
     *
     * @param  string  $pattern
     * @param  string  $value
     * @return bool
     */
    public static function is($pattern, $value)
    {
        if ($pattern == $value) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');

        // Asterisks are translated into zero-or-more regular expression wildcards
        // to make it convenient to check if the strings starts with the given
        // pattern such as "library/*", making any string check convenient.
        $pattern = str_replace('\*', '.*', $pattern);

        return (bool) preg_match('#^'.$pattern.'\z#', $value);
    }

    /**
     * Return the length of the given string.
     *
     * @param  string  $value
     * @return int
     */
    public static function length($value)
    {
        return mb_strlen($value);
    }


}
