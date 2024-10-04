<?php

namespace Omitsis\RedsysGooglePay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Email implements ObserverInterface
{
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer): void
    {
        try {
            $order = $observer->getEvent()->getOrder();
            if (!$order || !$order->getPayment()) {
                return;
            }

            $payment = $order->getPayment()->getMethodInstance();
            if ($payment && $payment->getCode() === 'gpay') {
                $this->stopNewOrderEmail($order);
            }
        } catch (\Exception $ex) {
            $this->logger->error('General Exception in Email Observer: ' . $ex->getMessage());
        }
    }

    /**
     * @param Order $order
     */
    public function stopNewOrderEmail(Order $order): void
    {
        $order->setCanSendNewEmailFlag(false);
        $order->setSendEmail(false);
        $order->setIsCustomerNotified(false);
        
        try {
            $order->save();
        } catch (\Exception $ex) {
            $this->logger->error('General Exception stopping new order email: ' . $ex->getMessage());
        }
    }
}
