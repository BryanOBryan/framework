<?php

use Kaliba\Foundation\Application;
use Kaliba\Http\Uri;
use Kaliba\Http\Redirector;
use Kaliba\Support\Cookie;
use Kaliba\Support\Session;
use Kaliba\Support\Facade;
use Kaliba\Support\Text;
use Kaliba\Support\Flash;
use Kaliba\Robas\Auth;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @return Kaliba\Foundation\Application|mixed
     */
    function app()
    {
        return Application::getInstance();
    }
}

if (!function_exists('path')){

    function path($path)
    {
        return app()->basePath($app);
    }
}

if (!function_exists('resource')){

    function resource($path)
    {
        return app()->resourcePath($path);
    }
}

if (!function_exists('storage')){

    function storage($path)
    {
        return app()->storagePath($path);
    }
}

if (!function_exists('database')){

    function database($name)
    {
        return app()->databasePath($name);
    }
}

if (!function_exists('uploads')){

    function uploads($path)
    {
        return base_url('uploads/'.$path);
    }
}

if (!function_exists('assets')){
	
    function assets($path)
    {
        return base_url('resource/assets/'.$path);
    }
}

if(!function_exists('flash')){

    function flash()
    {
        return Flash::instance();
    }
}

if(!function_exists('session')){

    function session()
    {
        return Session::instance();
    }
}

if(!function_exists('cookie')){

    function cookie()
    {
        return Cookie::instance();
    }
}

if(!function_exists('dump')){
    
    function dump($variable){
        print("<pre>");
        print_r($variable);    
    }
}

if (!function_exists('base_url')){

    function base_url($path=null)
    {
        $uri =  Uri::createFromGlobal();
        $baseUrl = $uri->getBaseUrl();
        if(!empty($path)){
            return $baseUrl.'/'.$path;
        }else{
            return $baseUrl;
        }
    }
}

if (!function_exists('route')){

    /**
     * @param string $route
     * @param string|int|array $data
     * @return string
     */
    function route($route, $data=null)
    {
        $uri =  Uri::makeUrl($route, $data);
        return (string)$uri;
    }
}

if (!function_exists('view')){

    /**
     * @param string $template
     * @param array $data
     *
     * @return string
     */
    function view($template, $data=[], $errors=[])
    {
        $view = app()->get('view');
        $view->data($data);
        $view->errors($errors);
        return $view->render($template);
    }
}

if (! function_exists('redirect')) {
    /**
     * Get an instance of the redirector.
     *
     * @param  string|null  $to
     * @param  int     $status
     * @param  array   $headers
     * @return \Kaliba\Http\Redirect
     */
    function redirect($location, $parameters = [])
    {
        $redirector = app()->make(Redirector::class);
        return $redirector->to($location, $parameters);
    }
}

if(!function_exists('csrf_token')){
	
    function csrf_token(){
        $token = session()->get("csrf_token");
        return $token;
    }
}

if(!function_exists('csrf_field')){
	
    function csrf_field(){
        $token = session()->get("csrf_token");
		print("<input name='csrf_token' type='hidden' value='{$token}' />");
    }
	
}

if (!function_exists('action')){

    /**
     * @param string $name
     * @param string $handler
     * @param mixed $bindings
     */
    function action($name, $handler,  $bindings=null, $class=null)
    {
        $data = [];
        if(is_string($bindings) || is_int($bindings)){
            $data['id'] = $bindings;
        } else{
            $data = $bindings;
        }
        $action= url($handler);
        $token = csrf_token();

        $html  = "<div class='{$class}' >";
        $html .= "<form action='{$action}' method='post'>";
        $html .= "<input name='csrf_token' type='hidden' value='{$token}' />";
        if(!empty($data)){
            foreach ($data as $key => $value){
                $html .= "<input name='{$key}' type='hidden' value='{$value}' />";
            }
        }
        $html .= "<input type='submit' value='{$name}'/>";
        $html .= "</form>";
        $html .= "</div>";
        print $html;
    }
}

if(!function_exists('auth'))
{
    function auth($group)
    {
        return Auth::group($group);
    }
}

if(!function_exists('permit'))
{
    function permit($action)
    {
        return Auth::permit($action);
    }
}


if (!function_exists('real_path')){

    function real_path($path)
    {
        return forward_slashes(realpath($path));
    }
}

if (!function_exists('add_slash')){
	
    function add_slash($url)
    {
        return rtrim($url, '/').'/';
    }

}

if (!function_exists('back_slashes')){
	
    function back_slashes($path)
    {
        return str_replace('/', '\\', $path);
    }
}

if (!function_exists('forward_slashes')){
	
    function forward_slashes($path)
    {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('sanitize')){
	
    function sanitize($input)
    {
        return htmlentities(trim($input));
    }
}

if(!function_exists('datetime')){
    
    function datetime(){
        return date('Y-m-d H:i:s');
    }
    
}

if(!function_exists('is_email')){
    function is_email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('is_cli')){
    /**
     * Is CLI?
     *
     * Test to see if a request was made from the command line.
     *
     * @return 	bool
     */
    function is_cli()
    {
        return (PHP_SAPI === 'cli' OR defined('STDIN'));
    }
}

if (!function_exists('is_windows')){
    /**
     * Checks whether the SERVER's plathtml is Windows
     * @return boolean
     */
    function is_windows()
    {
        if( strtolower(substr(PHP_OS, 0, 3)) === 'win' || DIRECTORY_SEPARATOR === '\\'){
            return true;
        }else{
            return false;
        }
        
    }
}

if (!function_exists('htmlspech')) {
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
    function htmlspech($text, $double = true, $charset = null)
    {
        return Text::htmlSpecialChars($text, $double, $charset);
    }

}

if (!function_exists('split')) {
    /**
     * Splits a dot syntax plugin name into its plugin and class name.
     * If $name does not have a dot, then index 0 will be null.
     *
     * Supportly used like
     * ```
     * list($plugin, $name) = split($name);
     * ```
     *
     * @param string $name The name you want to plugin split.
     * @param bool $dotAppend Set to true if you want the plugin to have a '.' appended to it.
     * @param string $plugin Optional default plugin to use if no plugin is found. Defaults to null.
     * @return array Array with 2 indexes. 0 => plugin name, 1 => class name.
     */
    function split($name, $dotAppend = false, $plugin = null)
    {
        return Text::splitPlugin($name, $dotAppend, $plugin);
    }

}

if (!function_exists('trim_slashes')){
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
     * @param	string
     * @return	string
     */
    function trim_slashes($str)
    {
        return  Text::trimSlashes($str);              
    }
}

if (!function_exists('strip_slashes')){
    /**
     * Strip Slashes
     *
     * Removes slashes contained in a string or in an array
     *
     * @param	mixed	string or array
     * @return	mixed	string or array
     */
    function strip_slashes($str)
    {
        return Text::stripSlashes($str);
    }
}

if (!function_exists('strip_quotes')){
    /**
     * Strip Quotes
     *
     * Removes single and double quotes from a string
     *
     * @param	string
     * @return	string
     */
    function strip_quotes($str)
    {
        return Text::stripQuotes($str);
    }
}

if (!function_exists('quotes_to_entities')){
    /**
     * Quotes to Entities
     *
     * Converts single and double quotes to entities
     *
     * @param	string
     * @return	string
     */
    function quotes_to_entities($str)
    {
        return Text::quotesToEntities($str);
    }
}

if (!function_exists('resolve_double_slashes')){
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
    function resolve_double_slashes($str)
    {
        return Text::resolveDoubleSlashes($str);
    }
}

if (!function_exists('resolve_multiples')){
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
    function resolve_multiples($str, $character = ',', $trim = FALSE)
    {
        return Text::resolveMultiples($str, $character, $trim);
    }
}

if (!function_exists('increment_string')){
    /**
     * Add's _1 to a string or increment the ending number to allow _2, _3, etc
     *
     * @param	string	required
     * @param	string	What should the duplicate number be appended with
     * @param	string	Which number should be used for the first dupe increment
     * @return	string
     */
    function increment_string($str, $separator = '_', $first = 1)
    {
        return Text::incrementString($str, $separator, $first);
    }
}

if (!function_exists('truncate_word')){
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
    function truncate_word($text, $length = 100, array $options = [])
    {
        return Text::truncate($text, $length, $options);
    }
}

if (!function_exists('limit_character')){
    /**
     * Character Limiter
     *
     * Limits the string based on the character count.  Preserves complete words
     * so the character count may not be exactly as specified.
     *
     * @param	string
     * @param	int
     * @param	string	the end character. Usually an ellipsis
     * @return	string
     */
    function limit_character($str, $n = 500, $end_char = '&#8230;')
    {
        if (mb_strlen($str) < $n)
        {
            return $str;
        }

        // a bit complicated, but faster than preg_replace with \s+
        $str = preg_replace('/ {2,}/', ' ', str_replace(array("\r", "\n", "\t", "\x0B", "\x0C"), ' ', $str));

        if (mb_strlen($str) <= $n)
        {
            return $str;
        }

        $out = '';
        foreach (explode(' ', trim($str)) as $val)
        {
            $out .= $val.' ';

            if (mb_strlen($out) >= $n)
            {
                $out = trim($out);
                return (mb_strlen($out) === mb_strlen($str)) ? $out : $out.$end_char;
            }
        }
	}
}

if (!function_exists('entities_to_ascii')){
    /**
     * Entities to ASCII
     *
     * Converts character entities back to ASCII
     *
     * @param	string
     * @param	bool
     * @return	string
     */
    function entities_to_ascii($str, $all = TRUE)
    {
        return Text::entitiesToAscii($str, $all);
    }
}

if (!function_exists('word_censor')){
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
    function word_censor($str, $censored, $replacement = '')
    {
       return Text::wordCensor($str, $censored, $replacement);
    }
}

if (!function_exists('highlight_code')){
    /**
     * Code Highlighter
     *
     * Colorizes code strings
     *
     * @param	string	the text string
     * @return	string
     */
    function highlight_code($str)
    {
        return Text::highlightCode($str);
    }
       
}

if (!function_exists('highlight')){
    /**
     * Highlights a given phrase in a text. You can specify any expression in highlighter that
     * may include the \1 expression to include the $phrase found.
     *
     * ### Options:
     *
     * - `htmlat` The piece of HTML with that the phrase will be highlighted
     * - `html` If true, will ignore any HTML tags, ensuring that only the correct text is highlighted
     * - `regex` a custom regex rule that is used to match words, default is '|$tag|iu'
     *
     * @param string $text Text to search the phrase in.
     * @param string|array $phrase The phrase or phrases that will be searched.
     * @param array $options An array of HTML attributes and options.
     * @return string The highlighted text
     *
     */
    function highlight($text, $phrase, array $options = [])
    {
        return Text::highlight($text, $phrase, $options);
    }
}

if (!function_exists('highlight_phrase')){
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
    function highlight_phrase($str, $phrase, $tag_open = '<mark>', $tag_close = '</mark>')
    {
        return Text::highlightPhrase($str, $phrase, $tag_open, $tag_close);
    }
}

if (!function_exists('word_wrap')){
    
    /**
     * Unicode and newline aware version of wordwrap.
     *
     * @param string $text The text to htmlat.
     * @param int $width The width to wrap to. Defaults to 72.
     * @param string $break The line is broken using the optional break parameter. Defaults to '\n'.
     * @param bool $cut If the cut is set to true, the string is always wrapped at the specified width.
     * @return string htmlatted text.
     */
    function word_wrap($text, $width = 72, $break = "\n", $cut = false)
    {      
        return Text::wordWrap($text, $width, $break, $cut);
    }
}

if (!function_exists('ellipsize')){
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
    function ellipsize($str, $max_length, $position = 1, $ellipsis = '&hellip;')
    {
        return Text::ellipsize($str, $max_length, $position, $ellipsis);
    }
}


if (!function_exists('now')){
    /**
     * Get "now" time
     *
     * Returns time() based on the timezone parameter or on the
     * "time_reference" setting
     *
     * @param	string
     * @return	int
     */
    function now($timezone = NULL)
    {
        if (empty($timezone)){
            $timezone = date_default_timezone_get();
        }

        if ($timezone === 'local' OR $timezone === date_default_timezone_get()){
            return time();
        }
        $datetime = new DateTime('now', new DateTimeZone($timezone));
        sscanf($datetime->htmlat('j-n-Y G:i:s'), '%d-%d-%d %d:%d:%d', $day, $month, $year, $hour, $minute, $second);

        return mktime($hour, $minute, $second, $month, $day, $year);
    }
}

if (!function_exists('remove_invisible_characters')){
    /**
     * Remove Invisible Characters
     *
     * This prevents sandwiching null characters
     * between ascii characters, like Java\0script.
     *
     * @param	string
     * @param	bool
     * @return	string
     */
    function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded){
            $non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

        do{
            $str = preg_replace($non_displayables, '', $str, -1, $count);
        }
        while ($count);

        return $str;
    }
}

if (!function_exists('html_escape')){
    /**
     * Returns HTML escaped variable.
     *
     * @param	mixed	$var		The input string or array of strings to be escaped.
     * @param	bool	$double_encode	$double_encode set to FALSE prevents escaping twice.
     * @return	mixed			The escaped string or array of strings as a result.
     */
    function html_escape($var, $double_encode = TRUE)
    {
        if (empty($var))
        {
            return $var;
        }

        if (is_array($var))
        {
            foreach (array_keys($var) as $key)
            {
                $var[$key] = html_escape($var[$key], $double_encode);
            }

            return $var;
        }

        return htmlspecialchars($var, ENT_QUOTES, 'utf8', $double_encode);
    }
}

if (!function_exists('stringify_attributes')){
    /**
     * Stringify attributes for use in HTML tags.
     *
     * Helper function used to convert a string, array, or object
     * of attributes to a string.
     *
     * @param	mixed	string, array, object
     * @param	bool
     * @return	string
     */
    function stringify_attributes($attributes, $js = FALSE)
    {
        $atts = NULL;
        if (empty($attributes)){
            return $atts;
        }

        if (is_string($attributes)){
            return ' '.$attributes;
        }

        $attributes = (array) $attributes;

        foreach ($attributes as $key => $val)
        {
            $atts .= ($js) ? $key.'='.$val.',' : ' '.$key.'="'.$val.'"';
        }

        return rtrim($atts, ',');
    }
}

if (!function_exists('entity_decode')){
    /**
     * HTML Entities Decode
     *
     * A replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @link	http://php.net/html-entity-decode
     *
     * @param	string	$str		Input
     * @param	string	$charset	Character set
     * @return	string
     */
    function entity_decode($str, $charset = NULL)
    {
        if (strpos($str, '&') === FALSE){
            return $str;
        }

        static $_entities;
        isset($charset) OR $charset = $this->charset;
        $flag = is_php('5.4')? ENT_COMPAT | ENT_HTML5 : ENT_COMPAT;
        do{
            $str_compare = $str;
            // Decode standard entities, avoiding false positives
            if (preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches)){
                    if ( ! isset($_entities)){
                            $_entities = array_map(
                                            'strtolower',
                                            is_php('5.3.4')
                                                            ? get_html_translation_table(HTML_ENTITIES, $flag, $charset)
                                                            : get_html_translation_table(HTML_ENTITIES, $flag)
                            );

                            // If we're not on PHP 5.4+, add the possibly dangerous HTML 5
                            // entities to the array manually
                            if ($flag === ENT_COMPAT)
                            {
                                    $_entities[':'] = '&colon;';
                                    $_entities['('] = '&lpar;';
                                    $_entities[')'] = '&rpar;';
                                    $_entities["\n"] = '&newline;';
                                    $_entities["\t"] = '&tab;';
                            }
                    }

                    $replace = array();
                    $matches = array_unique(array_map('strtolower', $matches[0]));
                    foreach ($matches as &$match)
                    {
                            if (($char = array_search($match.';', $_entities, TRUE)) !== FALSE)
                            {
                                    $replace[$match] = $char;
                            }
                    }

                    $str = str_ireplace(array_keys($replace), array_values($replace), $str);
            }

            // Decode numeric & UTF16 two byte entities
            $str = html_entity_decode(
                preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str),
                $flag,
                $charset
            );
        }
        while ($str_compare !== $str);
        return $str;
    }
  
}

if (!function_exists('get_random_bytes')){
    /**
     * Get random bytes
     *
     * @param	int	$length	Output length
     * @return	string
     */
    function get_random_bytes($length)
    {
        return openssl_random_pseudo_bytes($length);
    }
}

if (!function_exists('strip_image_tags')){
    /**
     * Strip Image Tags
     *
     * @param	string	$str
     * @return	string
     */
    function strip_image_tags($str)
    {
        return preg_replace(array('#<img[\s/]+.*?src\s*=\s*["\'](.+?)["\'].*?\>#', '#<img[\s/]+.*?src\s*=\s*(.+?).*?\>#'), '\\1', $str);
    }
}