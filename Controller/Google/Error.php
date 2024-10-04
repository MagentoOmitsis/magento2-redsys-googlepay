<?php

namespace Omitsis\RedsysGooglePay\Controller\Google;

use Omitsis\RedsysGooglePay\Helper\Redsys\OrderManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface; 
use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Data\Form\FormKey;
use Omitsis\RedsysGooglePay\Model\Payment\Gpay as RedsysModel;
use Omitsis\RedsysGooglePay\Logger\Handler\Logger;

/**
 * Controller class to handle payment error page and restore cart if needed.
 */
class Error extends \Magento\Framework\App\Action\Action {

    /**
     * @var Session
     */
    private $session;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Cart
     */
    private $cart;

    /**
     * @var FormKey
     */
    private $formKey;

    /**
     * @var RedsysModel
     */
    private $redsysModel;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderManager
     */
    private $orderManager;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $session
     * @param StoreManagerInterface $storeManager
     * @param ProductRepository $productRepository
     * @param Cart $cart
     * @param FormKey $formKey
     * @param RedsysModel $redsysModel
     * @param Logger $logger
     * @param OrderManager $orderManager
     */
    public function __construct(
        Context $context, 
        PageFactory $resultPageFactory, 
        Session $session, 
        StoreManagerInterface $storeManager, 
        ProductRepository $productRepository, 
        Cart $cart,
        FormKey $formKey,
        RedsysModel $redsysModel,
        Logger $logger,
        OrderManager $orderManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->cart = $cart;
        $this->formKey = $formKey;
        $this->redsysModel = $redsysModel;
        $this->logger = $logger;
        $this->orderManager = $orderManager;

        parent::__construct($context);
    }

    /**
     * Main controller action for handling the error page after a payment failure.
     * 
     * - If the form key is valid and the cart should be restored, it restores the cart.
     * - It sets the error status depending on the situation.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute() {
        $order = $this->session->getLastRealOrder();

        $formKeyToUse = $this->getRequest()->getParam('form_key') ?? $this->formKey->getFormKey();

        $saveCart = $this->redsysModel->getConfigData('errorpago');

        if (!$formKeyToUse) {
            $estado = 0;
        } elseif ($saveCart) {
            $estado = 1;
            $this->orderManager->restoreCart($order, $this->cart, $formKeyToUse, $this->productRepository, $this->logger);
        } else {
            $estado = 2;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend("Error procesando el pago");
        $resultPage->getLayout()->initMessages();
        $resultPage->getLayout()->getBlock('mpay_google_error')->setEstado($estado);

        return $resultPage;
    }
}
