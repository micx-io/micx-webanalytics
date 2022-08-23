<?php

namespace Micx\FormMailer\Config;

class T_Analytics
{
    /**
     * The EMail to send Reports to
     *
     * @var string
     */
    public string $report_email;

    /**
     * Autostart Analytics w/o CookieConstent
     *
     * @var bool
     */
    public bool $autostart = false;

}
