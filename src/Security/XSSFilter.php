<?php
namespace Kaliba\Security;

/**
 * XSSFilter Class from CodeIgniter
 *
 */
final class XSSFilter
{

    /**
     * Character set
     *
     * Will be overridden by the constructor.
     *
     * @var	string
     */
    protected $charset = 'UTF-8';

    /**
     * XSS Hash
     *
     * Random Hash for protecting URLs.
     *
     * @var	string
     */
    protected $hash;

    /**
     * List of never allowed strings
     *
     * @var	array
     */
    protected $never_allowed_str =	array(
        'document.cookie'   => '[removed]',
        'document.write'    => '[removed]',
        '.parentNode'       => '[removed]',
        '.innerHTML'        => '[removed]',
        '-moz-binding'      => '[removed]',
        '<!--'              => '&lt;!--',
        '-->'               => '--&gt;',
        '<![CDATA['         => '&lt;![CDATA[',
        '<comment>'         => '&lt;comment&gt;'
    );

    /**
     * List of never allowed regex replacements
     *
     * @var	array
     */
    protected $never_allowed_regex = array(
        'javascript\s*:',
        '(document|(document\.)?window)\.(location|on\w*)',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'wscript\s*:', // IE
        'jscript\s*:', // IE
        'vbs\s*:', // IE
        'Redirect\s+30\d',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    );

	
    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This method does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: Should only be used to deal with data upon submission.
     *	 It's not something that should be used for general
     *	 runtime processing.
     *
     * @link	http://channel.bitflux.ch/wiki/XSS_Prevention
     * 		Based in part on some code and ideas from Bitflux.
     *
     * @link	http://hackers.org/xss.html
     * 		To help develop this script I used this great list of
     *		vulnerabilities along with a few other hacks I've
     *		harvested from examining vulnerabilities in other programs.
     *
     * @param	string|string[]	$str		Input data
     * @param 	bool		$is_image	Whether the input is an image
     * @return	string
     */
    public function clean($str, $is_image = FALSE)
    {
        if (is_array($str)){
            while (list($key) = each($str)){
                $str[$key] = $this->clean($str[$key]);
            }
            return $str;
        }

        $str = remove_invisible_characters($str);
        do{
            $str = rawurldecode($str);
        }while (preg_match('/%[0-9a-f]{2,}/i', $str));

        $str = preg_replace_callback("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array($this, 'convert_attribute'), $str);
        $str = preg_replace_callback('/<\w+.*/si', array($this, 'decode_entity'), $str);
        $str = remove_invisible_characters($str);
        $str = str_replace("\t", ' ', $str);
        $converted_string = $str;
        
        $str = $this->do_never_allowed($str);

        if ($is_image === TRUE){
           $str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
        }
        else{
            $str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
        }

        $words = array(
            'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
            'vbs', 'script', 'base64', 'applet', 'alert', 'document',
            'write', 'cookie', 'window', 'confirm', 'prompt', 'eval'
        );

        foreach ($words as $word){
            $word = implode('\s*', str_split($word)).'\s*';
            $str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, 'compact_exploded_words'), $str);
        }
        do{
            $original = $str;
            if (preg_match('/<a/i', $str)){
                $str = preg_replace_callback('#<a[^a-z0-9>]+([^>]*?)(?:>|$)#si', array($this, 'js_link_removal'), $str);
            }

            if (preg_match('/<img/i', $str)){
                $str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array($this, 'js_img_removal'), $str);
            }
            if (preg_match('/script|xss/i', $str)) {
                $str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
            }
        }while ($original !== $str);
        unset($original);

        $pattern = '#'
                .'<((?<slash>/*\s*)(?<tagName>[a-z0-9]+)(?=[^a-z0-9]|$)' // tag start and name, followed by a non-tag character
                .'[^\s\042\047a-z0-9>/=]*' // a valid attribute character immediately after the tag would count as a separator
                // optional attributes
                .'(?<attributes>(?:[\s\042\047/=]*' // non-attribute characters, excluding > (tag close) for obvious reasons
                .'[^\s\042\047>/=]+' // attribute characters
                // optional attribute-value
                        .'(?:\s*=' // attribute-value separator
                                .'(?:[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*))' // single, double or non-quoted value
                        .')?' // end optional attribute-value group
                .')*)' // end optional attributes group
                .'[^>]*)(?<closeTag>\>)?#isS';

        do{
            $old_str = $str;
            $str = preg_replace_callback($pattern, array($this, 'sanitize_naughty_html'), $str);
        }while ($old_str !== $str);
        unset($old_str);
        $str = preg_replace(
            '#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
            '\\1\\2&#40;\\3&#41;',
            $str
        );

        $str = $this->do_never_allowed($str);
        if ($is_image === TRUE)
        {
            return ($str === $converted_string);
        }

        return $str;
    }

    /**
	 * XSS Hash
	 *
	 * Generates the XSS hash if needed and returns it.
	 *
	 * @see		CI_Security::$hash
	 * @return	string	XSS hash
	 */
    public function hash()
    {
        if ($this->hash === NULL)
        {
            $rand = get_random_bytes(16);
            $this->hash = ($rand === FALSE)? md5(uniqid(mt_rand(), TRUE)): bin2hex($rand);
        }
        return $this->hash;
    }
       
    /**
     * Compact Exploded Words
     *
     * Callback method for clean() to remove whitespace from
     * things like 'j a v a s c r i p t'.
     *
     * @used-by	CI_Security::clean()
     * @param	array	$matches
     * @return	string
     */
    protected function compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }

    /**
     * Sanitize Naughty HTML
     *
     * Callback method for clean() to remove naughty HTML elements.
     *
     * @param	array	$matches
     * @return	string
     */
    protected function sanitize_naughty_html($matches)
    {
        static $naughty_tags    = array(
            'alert', 'prompt', 'confirm', 'applet', 'audio', 'basefont', 'base', 'behavior', 'bgsound',
            'blink', 'body', 'embed', 'expression', 'form', 'frameset', 'frame', 'head', 'html', 'ilayer',
            'iframe', 'input', 'button', 'select', 'isindex', 'layer', 'link', 'meta', 'keygen', 'object',
            'plaintext', 'style', 'script', 'textarea', 'title', 'math', 'video', 'svg', 'xml', 'xss'
        );

        static $evil_attributes = array(
                'on\w+', 'style', 'xmlns', 'formaction', 'form', 'xlink:href', 'FSCommand', 'seekSegmentTime'
        );

        if (empty($matches['closeTag']))
        {
            return '&lt;'.$matches[1];
        }
        elseif (in_array(strtolower($matches['tagName']), $naughty_tags, TRUE))
        {
            return '&lt;'.$matches[1].'&gt;';
        }
        elseif (isset($matches['attributes']))
        {
            // We'll store the already fitlered attributes here
            $attributes = array();

            // Attribute-catching pattern
            $attributes_pattern = '#'
                    .'(?<name>[^\s\042\047>/=]+)' // attribute characters
                    // optional attribute-value
                    .'(?:\s*=(?<value>[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*)))' // attribute-value separator
                    .'#i';

            // Blacklist pattern for evil attribute names
            $is_evil_pattern = '#^('.implode('|', $evil_attributes).')$#i';
            do
            {
                $matches['attributes'] = preg_replace('#^[^a-z]+#i', '', $matches['attributes']);

                if ( ! preg_match($attributes_pattern, $matches['attributes'], $attribute, PREG_OFFSET_CAPTURE)){
                    break;
                }

                if ( preg_match($is_evil_pattern, $attribute['name'][0]) || (trim($attribute['value'][0]) === ''))
                {
                    $attributes[] = 'xss=removed';
                }
                else
                {
                    $attributes[] = $attribute[0][0];
                }

                $matches['attributes'] = substr($matches['attributes'], $attribute[0][1] + strlen($attribute[0][0]));
            }
            while ($matches['attributes'] !== '');

            $attributes = empty($attributes)? '': ' '.implode(' ', $attributes);
            return '<'.$matches['slash'].$matches['tagName'].$attributes.'>';
        }

        return $matches[0];
    }

    /**
     * JS Link Removal
     *
     * Callback method for clean() to sanitize links.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings.
     *
     * @used-by	CI_Security::clean()
     * @param	array	$match
     * @return	string
     */
    protected function js_link_removal($match)
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
                '',
                $this->clean_attributes($match[1])
            ),
            $match[0]
        );
    }

    /**
     * JS Image Removal
     *
     * Callback method for clean() to sanitize image tags.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings.
     *
     * @used-by	CI_Security::clean()
     * @param	array	$match
     * @return	string
     */
    protected function js_img_removal($match) 
    {
        return str_replace(
            $match[1],
            preg_replace(
                '#src=.*?(?:(?:alert|prompt|confirm|eval)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
                '',
                $this->clean_attributes($match[1])
            ),
            $match[0]
        );
    }

    /**
     * Attribute Conversion
     *
     * @used-by	CI_Security::clean()
     * @param	array	$match
     * @return	string
     */
    protected function convert_attribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety.
     *
     * @used-by	CI_Security::js_img_removal()
     * @used-by	CI_Security::js_link_removal()
     * @param	string	$str
     * @return	string
     */
    protected function clean_attributes($str)
    {
        $out = '';
        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
        {
            foreach ($matches[0] as $match)
            {
                $out .= preg_replace('#/\*.*?\*/#s', '', $match);
            }
        }

        return $out;
    }

    /**
     * HTML Entity Decode Callback
     *
     * @used-by	CI_Security::clean()
     * @param	array	$match
     * @return	string
     */
    protected function decode_entity($match)
    {
        // Protect GET variables in URLs
        // 901119URL5918AMP18930PROTECT8198
        $match = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->hash().'\\1=\\2', $match[0]);

        // Decode, then un-protect URL GET vars
        return str_replace(
            $this->hash(),
            '&',
            entity_decode($match, $this->charset)
        );
    }

    /**
     * Do Never Allowed
     *
     * @used-by	CI_Security::clean()
     * @param 	string
     * @return 	string
     */
    protected function do_never_allowed($str)
    {
        $str = str_replace(array_keys($this->never_allowed_str), $this->never_allowed_str, $str);

        foreach ($this->never_allowed_regex as $regex)
        {
            $str = preg_replace('#'.$regex.'#is', '[removed]', $str);
        }

        return $str;
    }

}
