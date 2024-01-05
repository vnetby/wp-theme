<?php

namespace Vnetby\Wptheme\Traits\Config;

trait ConfigMailer
{
    /**
     * - Почта администатора
     */
    protected string $emailAdmin = '';

    /**
     * - Имя отправителя для системных сообщений
     */
    protected string $emailFromName = '';

    /**
     * - Почта от которой будут отправляться письма
     */
    protected string $emailFrom = '';

    protected bool $useSmtp = false;

    protected string $smtpHost = '127.0.0.1';

    protected int $smtpPort = 587;

    protected string $smtpUser;

    protected string $smtpPass;

    protected bool $useSmtpSsl = true;

    protected bool $useSmtpTls = false;

    function setSmtp(bool $smtp)
    {
        $this->useSmtp = $smtp;
        return $this;
    }

    function smtp(): bool
    {
        return $this->useSmtp;
    }

    function setSmtpHost(string $host)
    {
        $this->smtpHost = $host;
        return $this;
    }

    function getSmtpHost(): string
    {
        return $this->smtpHost;
    }

    function setSmtpPort(int $port)
    {
        $this->smtpPort = $port;
        return $this;
    }

    function getSmtpPort(): int
    {
        return $this->smtpPort;
    }

    function setSmtpUser(string $user)
    {
        $this->smtpUser = $user;
        return $this;
    }

    function getSmtpUser(): string
    {
        return $this->smtpUser;
    }

    function setSmtpPass(string $pass)
    {
        $this->smtpPass = $pass;
        return $this;
    }

    function getSmtpPass(): string
    {
        return $this->smtpPass;
    }

    function setSmtpSsl(bool $ssl)
    {
        $this->useSmtpSsl = $ssl;
        $this->useSmtpTls = !$ssl;
        return $this;
    }

    function smtpSsl(): bool
    {
        return $this->useSmtpSsl;
    }

    function setSmtpTls(bool $tls)
    {
        $this->useSmtpTls = $tls;
        $this->useSmtpSsl = !$tls;
        return $this;
    }

    function smtpTls(): bool
    {
        return $this->useSmtpTls;
    }


    function getEmailAdmin(): string
    {
        return $this->emailAdmin;
    }

    /**
     * @param string $email
     * @return static
     */
    function setEmailAdmin(string $email)
    {
        $this->emailAdmin = $email;
        return $this;
    }

    function getEmailFromName(): string
    {
        return $this->emailFromName;
    }

    /**
     * @param string $fromName
     * @return static
     */
    function setEmailFromName(string $fromName)
    {
        $this->emailFromName = $fromName;
        return $this;
    }

    function getEmailFrom(): string
    {
        return $this->emailFrom;
    }

    /**
     * @param string $emailFrom
     * @return static
     */
    function setEmailFrom(string $emailFrom)
    {
        $this->emailFrom = $emailFrom;
        return $this;
    }
}
