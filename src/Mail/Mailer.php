<?php
namespace Kaliba\Mail;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer
{
    /**
     * @var PHPMailer
     */
    protected $mailer;

    /**
     * Mailer constructor.
     * @param PHPMailer $mailer
     */
    public function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Set the global from address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysFrom($address, $name = null)
    {
        $this->mailer->setFrom($address, $name);
    }

    /**
     * Set the global reply-to address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysReplyTo($address, $name = null)
    {
        $this->mailer->addReplyTo($address, $name);
    }

    /**
     * Set the global to address and name.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return void
     */
    public function alwaysTo($address, $name = null)
    {
        $this->mailer->addAddress($address, $name);
    }

    public function send(\Email $email)
    {

    }


}