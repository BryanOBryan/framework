<?php

namespace Kaliba\Network;

use RuntimeException;

/**
 * Exception class for Socket. This exception will be thrown from Socket, Email, HttpSocket
 * SmtpTransport, MailTransport and HttpResponse when it encounters an error.
 *
 */
class SocketException extends RuntimeException
{
}
