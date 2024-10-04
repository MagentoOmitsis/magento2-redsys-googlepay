<?php

namespace Omitsis\RedsysGooglePay\Controller\Google;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Checkout\Model\Session;
use Redsys\Redsys\Helper\Constants\RESTConstants;
use Redsys\Redsys\Helper\CurrencyManager;
use Redsys\Redsys\Helper\Model\message\RESTOperationMessage;
use Omitsis\RedsysGooglePay\Helper\Redsys\OrderManager;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\App\RequestInterface;
use Redsys\Redsys\Helper\RedsysLibrary;
use Redsys\Redsys\Helper\Service\Impl\RESTOperationService;
use Omitsis\RedsysGooglePay\Model\Payment\Gpay as RedsysModel;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Data\Form\FormKey;
use Omitsis\RedsysGooglePay\Logger\Handler\Logger;
use Magento\Framework\UrlInterface;

/**
 * Controller class to handle the success response from Google Pay through Redsys.
 */
class Success implements HttpGetActionInterface
{
    const XPAYORIGEN = 'WEB';
    const XPAYTYPE = 'Google';
    const CURRENCY = '978';

    protected $sisCode;

    /**
     * @param Session $session
     * @param RequestInterface $request
     * @param UrlInterface $urlInterface
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param ResultFactory $resultFactory
     * @param RedsysModel $redsysModel
     * @param SerializerInterface $serializer
     * @param FormKey $formKey
     * @param Logger $logger
     */
    public function __construct(
        private readonly Session $session,
        private readonly RequestInterface $request,
        private readonly UrlInterface $urlInterface,
        private readonly InvoiceService $invoiceService,
        private readonly InvoiceSender $invoiceSender,
        private readonly ResultFactory $resultFactory,
        private readonly RedsysModel $redsysModel,
        private readonly SerializerInterface $serializer,
        private readonly FormKey $formKey,
        private readonly Logger $logger
    ) {
        $this->sisCode = '';
    }

    /**
     * Main method executed when the payment is successful.
     * Handles order processing, sends operation to Redsys, and logs the results.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $order = $this->session->getLastRealOrder();
        $orderId = $order->getId();

        $this->logger->info('############################################');
        
        if ($orderId) {

            $idLog = RedsysLibrary::generateIdLog($this->redsysModel->getConfigData('logactivo'), $orderId);

            $orderItems = $order->getAllItems();
            $amount = CurrencyManager::GetAmount($order->getTotalDue(), CurrencyManager::GetCurrency());
            $numpedido = RedsysLibrary::generaNumeroPedido($orderId, $this->redsysModel->getConfigData('gateway_genpedido'), $this->redsysModel->getConfigData('gateway_pedidoextendido'));
            $titular = $order->getCustomerFirstname() . " " . $order->getCustomerLastname() . " / " . __("Correo") . ": " . $order->getCustomerEmail();

            $productdescription = "";
            foreach ($orderItems as $item) {
                $cant = ($item->getQtyOrdered() % 1 != 0) ? $item->getQtyOrdered() : intval($item->getQtyOrdered());
                $productdescription .= $item->getName() . " x " . $cant . " / ";
            }

            $request = new RESTOperationMessage();
            $request->setAmount($amount);
            $request->setCurrency(self::CURRENCY);
            $request->setMerchant($this->redsysModel->getConfigData('gateway_merchantId'));
            $request->setTerminal($this->redsysModel->getConfigData('gateway_terminal'));
            $request->setOrder($numpedido);
            $request->setTransactionType(intval($this->redsysModel->getConfigData('gateway_tipopago')));
            $request->addParameter("DS_MERCHANT_TITULAR", $titular);
            $request->addParameter("DS_MERCHANT_PRODUCTDESCRIPTION", $productdescription);
            $request->addParameter("DS_MERCHANT_MODULE", 'MP-GPAY');
            $ip = $_SERVER['REMOTE_ADDR'] == "::1" ? "127.0.0.1" : $_SERVER['REMOTE_ADDR'];
            $request->addParameter("DS_MERCHANT_CLIENTIP", $ip);
            $request->addParameter("DS_XPAYDATA", $this->request->getParam('paymentToken'));
            $request->addParameter("DS_XPAYTYPE", self::XPAYTYPE);
            $request->addParameter("DS_XPAYORIGEN", self::XPAYORIGEN);
            $request->useDirectPayment();

            $response = $this->sendOperation($request, $idLog);

            if ($response->getResult() == RESTConstants::$RESP_LITERAL_OK) {

                $pedido = $response->getOperation()->getOrder();
                OrderManager::SaveOrder($order, $this->redsysModel, $this->invoiceService, $this->invoiceSender, $pedido, $idLog, $this->logger);
                $redirect->setUrl($this->urlInterface->getBaseUrl() . 'checkout/onepage/success');
            } else {

                $fullComment = 'FAIL REDSYS - SIS: ' . $this->getSisCode() . "\n\n" . 'ID LOG: ' . $idLog;
                $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, 'canceled', 'El pedido ha sido cancelado.', false)->save();
                $order->addStatusHistoryComment($fullComment, false)->setIsCustomerNotified(false)->save();

                $this->logger->info('FAIL - ' . $idLog . ' - PEDIDO: ' . $orderId . ' CANCELADO');
                $redirect->setUrl($this->urlInterface->getBaseUrl() . 'mpay/google/error?form_key=' . $this->formKey->getFormKey());
            }
        }

        $this->logger->info('############################################');
        return $redirect;
    }

    /**
     * Send the operation to the Redsys payment gateway.
     *
     * @param RESTOperationMessage $message
     * @param string|null $idLog
     * @return mixed
     */
    private function sendOperation($message, $idLog = null)
    {
        $result = "";
        $service = new RESTOperationService($this->redsysModel->getConfigData('gateway_clave256'), $this->redsysModel->getConfigData('gateway_entorno'));

        $this->logger->info('INFO - ' . $idLog . ' - START FUNCTION SEND OPERATION');

        $post_request = $service->createRequestSOAPMessage($message, $idLog);
        $header = ["Cache-Control: no-cache", "Pragma: no-cache", "Content-length: " . strlen($post_request)];
        $url_rs = RESTConstants::getEnviromentEndpoint($this->redsysModel->getConfigData('gateway_entorno'), 1);

        $rest_do = curl_init();
        curl_setopt($rest_do, CURLOPT_URL, $url_rs);
        curl_setopt($rest_do, CURLOPT_CONNECTTIMEOUT, RESTConstants::$CONNECTION_TIMEOUT_VALUE);
        curl_setopt($rest_do, CURLOPT_TIMEOUT, RESTConstants::$READ_TIMEOUT_VALUE);
        curl_setopt($rest_do, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($rest_do, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($rest_do, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($rest_do, CURLOPT_SSLVERSION, RESTConstants::$SSL_TLSv12);
        curl_setopt($rest_do, CURLOPT_POST, true);
        curl_setopt($rest_do, CURLOPT_POSTFIELDS, $post_request);
        curl_setopt($rest_do, CURLOPT_HTTPHEADER, $header);

        $tmp = curl_exec($rest_do);
        $httpCode = curl_getinfo($rest_do, CURLINFO_HTTP_CODE);

        $this->logger->info('INFO - ' . $idLog . ' - URL REST: ' . print_r($url_rs, true));
        $this->logger->info('INFO - ' . $idLog . ' - DATOS ENVIADOS A REDSYS: ' . print_r($post_request, true));
        $this->logger->info('INFO - ' . $idLog . ' - TEMP EXEC: ' . print_r($tmp, true));

        if ($tmp !== false && $httpCode == 200) {
            $result = $tmp;
        } else {
            $this->logger->info("FAIL - " . $idLog . " - SOLICITUD FALLIDA: " . (($httpCode != 200) ? "[HttpCode: '" . $httpCode . "']" : "") . ((curl_error($rest_do)) ? " [Error: '" . curl_error($rest_do) . "']" : ""));
        }

        $this->logger->info('INFO - ' . $idLog . ' - RESULT EXEC: ' . print_r($result, true));

        curl_close($rest_do);

        $varArray = json_decode($result, true);
        if (array_key_exists("errorCode", $varArray)) {
            $this->setSisCode($varArray["errorCode"]);
        }

        return $service->createResponseMessage($result, $idLog);
    }

    /**
     * Getter for the SIS error code.
     *
     * @return string
     */
    private function getSisCode()
    {
        return $this->sisCode;
    }

    /**
     * Setter for the SIS error code.
     *
     * @param string $sisCode
     */
    private function setSisCode($sisCode)
    {
        $this->sisCode = $sisCode;
    }
}
