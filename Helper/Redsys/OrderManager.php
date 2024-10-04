<?php

namespace Omitsis\RedsysGooglePay\Helper\Redsys;

use Magento\Framework\DB\Transaction;

class OrderManager {

	public static function SaveOrder($order, $redsysModel, $invoiceService, $invoiceSender, $pedido, $idLog, $logger) {
		try {
			$estado = $redsysModel->getConfigData('estado');
			$order->setBaseTotalPaid($order->getBaseTotalDue());
			$order->setTotalPaid($order->getTotalDue());
			$order->setState('new')->setStatus($estado)->save();
			$order->addStatusHistoryComment(__("Pago con Redsys registrado con éxito."), false)
				->setIsCustomerNotified(false)
				->save();
			
			if(!$order->canInvoice()) {
				$order->addStatusHistoryComment(__("Redsys, imposible generar Factura."), false);
				$order->save();
			} else {
				$transaction = new Transaction();
				$invoice = $invoiceService->prepareInvoice($order);
				$invoice->register();
				$invoice->setTransactionId($pedido);
				$invoice->pay();
				$invoice->save();
				$transactionSave = $transaction->addObject($invoice)->addObject($invoice->getOrder());
				$transactionSave->save();
				if (!@$invoiceSender->send($invoice)){
					$order->addStatusHistoryComment(__("Redsys, imposible enviar Factura."), false);
				}
				$order->addStatusHistoryComment(__("Redsys ha generado la Factura del pedido"), false)->save();
			}

			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
			$emailSender->send($order);

			if ($redsysModel->getConfigData('correo')) {
				$nombreComercio = $redsysModel->getConfigData('gateway_merchantName');
				$mensaje = $redsysModel->getConfigData('mensaje');
				$email_to = $order->getCustomerEmail();
				$email_subject = "-MAGENTO- Pedido realizado";
				if (!@mail($email_to, $email_subject, $mensaje, "From:" . $nombreComercio)) {
					$order->addStatusHistoryComment(__("Redsys, imposible enviar correo."), false);
				}
			}
		}
		catch (\Exception $e) {
			$order->addStatusHistoryComment('Redsys: Exception message: '.$e->getMessage(), false);
			$order->save();

			$logger->info("ERROR", $idLog, "Pedido " . $order->getId() . ". Excepción " . $e);
		}
	}

	public static function RestoreCart($order, $cart, $formKey, $productRepository, $logger)
	{
		if ($order && $cart->getItemsCount() == 0) {
			$orderItems = $order->getAllItems();
			if ($orderItems) {
				foreach ($orderItems as $item) {
					$info = $item->getProductOptionByCode('info_buyRequest');
					$params = array(
						"form_key" => $formKey
					);

					if (is_array($info)) {
						$params = array_merge($params, $info);
					}

					try {
						$product = $productRepository->getById($item->getProductId());

						$cart->addProduct($product, $params);
					} catch (\Exception $e) {
						$logger->info("Error al añadir el producto ID " . $item->getProductId() . " al carrito: " . $e->getMessage());
					}
				}
				$cart->save();
			}
		}
	}

}
