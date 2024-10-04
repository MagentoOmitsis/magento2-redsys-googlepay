<?php

namespace Omitsis\RedsysGooglePay\Logger\Handler;

use Magento\Framework\Logger\Handler\Base as SourceBaseHandler;
use Monolog\Logger as MonologLogger;

class BaseHandler extends SourceBaseHandler
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = MonologLogger::DEBUG;

    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/googlePay.log';
}
