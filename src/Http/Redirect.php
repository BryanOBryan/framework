<?php

namespace Kaliba\Http;

use Kaliba\Support\Flash;

class Redirect extends Response
{
    protected $targetUrl;

    /**
     * @var Flash
     */
    protected $flash;

    /**
     * Creates a redirect response so that it conforms to the rules defined for a redirect status code.
     *
     * @param string $url     The URL to redirect to. The URL should be a full URL, with schema etc.,
     *                        but practically every browser redirects on paths only as well
     * @param int    $status  The status code (302 by default)
     * @param array  $headers The headers (Location is always set to the given URL)
     *
     * @throws \InvalidArgumentException
     *
     * @see http://tools.ietf.org/html/rfc2616#section-10.3
     */
    public function __construct($url, $status = 302, $headers = array())
    {
        parent::__construct('', $status, $headers);

        $this->flash = Flash::instance();

        $this->targetUrl($url);

        if (!$this->isRedirection()) {
            throw new \InvalidArgumentException(sprintf('The HTTP status code is not a redirect ("%s" given).', $status));
        }

        if (301 == $status && !array_key_exists('cache-control', $headers)) {
            $this->headers->remove('cache-control');
        }
    }

    /**
     * Sets the redirect target of this response.
     *
     * @param string $url The URL to redirect to
     *
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function targetUrl($url)
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Cannot redirect to an empty URL.');
        }

        $this->targetUrl = $url;

        $this->setContent(
            sprintf('<!DOCTYPE html>
                <html>
                    <head>
                        <meta charset="UTF-8" />
                        <meta http-equiv="refresh" content="1;url=%1$s" />
                
                        <title>Redirecting to %1$s</title>
                    </head>
                    <body>
                        Redirecting to <a href="%1$s">%1$s</a>.
                    </body>
                </html>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8')));

        $this->headers->set('Location', $url);

        return $this;
    }

    /**
     * @param string $message
     * @return self
     */
    public function success($message)
    {
        $this->flash->success($message);
        return $this;
    }

    /**
     * @param string $message
     * @return self
     */
    public function error($message)
    {
        $this->flash->error($message);
        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function info($message)
    {
        $this->flash->info($message);
        return $this;
    }

}