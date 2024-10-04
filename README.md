# Magento 2 Redsys Google Pay Plugin

Este módulo complementa al [plugin oficial de Redsys](https://pagosonline.redsys.es/descargas.html) para Magento 2, permitiendo la integración de Google Pay como método de pago adicional.
A través de este plugin, los clientes pueden utilizar Google Pay directamente desde la página de checkout, sin necesidad de pasar por la pasarela de pago de Redsys.

![Modal de Google Pay en página checkout](https://drive.google.com/uc?export=view&id=1wzo0lgjv8LSvG6plPbOennoYT4DCGTpZ)

Esto corresponde al modelo de [Integración directa de Redsys con Google Pay](https://pagosonline.redsys.es/googlePay.html#integraciondirecta)

## Requisitos

- **Plugin Oficial de Redsys**: Este plugin requiere que el plugin oficial de Redsys esté instalado y configurado en tu tienda Magento 2.
- **Cuenta con Redsys y Google Pay**: Para utilizar este módulo, debes tener una cuenta con Redsys y seguir sus requisitos para la integración con Google Pay. Deben activarte en concreto el pago de Google Pay por integración.
- **Cuenta de Google Business**: Debes crear una [cuenta de Google Pay & Wallet](https://pay.google.com/business/console) y dentro configurar y validar una integración con el dominio de la tienda. [Sigue esta guía para la modalidad web](https://developers.google.com/pay/api/web/overview) teniendo en cuenta las [brand guidelines para pasar la validación](https://developers.google.com/pay/api/web/guides/brand-guidelines) y [solicitar el pase a producción cuando lo tengas todo listo y probado](https://developers.google.com/pay/api/web/guides/test-and-deploy/publish-your-integration)

## Consideraciones importanes

No vamos a explicar aquí como configurar o empezar a trabajar con Redsys ni el proceso completo para crear la integración con Google, puesto que ya lo hace Redsys en sus guías.
Pero sí vamos a remarcar algunos apuntes a tener en cuenta.

- **Debes tener activado en tu banco este tipo de pago**: En concreto Google Pay con integración.
- **Debes tener activado el modalidad MIXTA en la cuenta**: La modalidad mixta nos habilita poder realizar operaciones no seguras contra la pasarela de Redsys, es decir, operaciones en las que no hace falta que Redsys valide las tarjetas ya que en este caso lo hara Google al registrar las tarjetas en GPay. Debes solicitar al banco esta configuración.
- **Diferencia entre operaciones tokenizadas para Apps y las no tokenizadas para web**: En las operaciones con tarjeta sin tokenizar, Google cifra la tarjeta en el mensaje que posteriormente el comercio envía a Redsys. Esta operación debe ser autenticada y es en ese punto donde la modalidad MIXTA es necesaria.
- **En el entorno de pruebas de Redsys no podrás probar todo el ciclo de pago**: La pasarela de pagos en entorno TEST de Redsys no estápreparada para simular del todo un pedido con integración de Google Pay, no habiendo notificación al comercio y por lo tanto deberás confiar y probar el proceso completo ya en el entorno REAL.

## Características

- **Integración directa con Google Pay**: Permite a los clientes pagar con Google Pay directamente desde el checkout, sin redirigirlos a la pasarela de Redsys.
- **Sin necesidad de cumplimiento PCI**: Como es Google quien valida las tarjetas, no es necesario gestionar directamente los datos de las tarjetas ni cumplir con las normativas PCI.
- **Configuración sencilla**: Configura el plugin fácilmente desde el panel de administración de Magento, añadiendo los parámetros necesarios para comunicarte con Redsys y el servicio de Google.

## Instalación

1. Asegúrate de que el plugin oficial de Redsys esté instalado y configurado correctamente en tu tienda Magento 2.
2. Instala este plugin:

	#### Utilizando composer

	Puedes descargarte el plugin directamente puesto que está registrado en packagist.org

    ```bash
    composer require omitsis/redsys-googlepay
    ```

	#### Por copia directa de archivos

	* Descarga la extensión
	* Descomprime el archivo
	* Crea el directorio app/code/Omitsis/RedsysGooglePay  
	* Copia el contenido del archivo a esa carpeta

3. Habilita el módulo ejecutando los siguientes comandos:

    ```bash
    bin/magento module:enable Omitsis_RedsysGooglePay
    bin/magento setup:upgrade
    bin/magento cache:clean
    ```

4. Configura el plugin desde el panel de administración.

![Configuración general](https://drive.google.com/uc?export=view&id=1ylhVNjWZ9_5CxUgdWFadIADCoZHauTda)

## Configuración

1. Ve a **Stores > Configuration > Sales > Payment Methods** en el panel de administración de tu Magento.

2. Busca la sección "Redsys Pago con Google Pay".

3. Completa los campos requeridos, la configuracion se divide en tres pestañas:
   #### Configuración General:
   - **Habilitar**: Habilitar/Deshabilitar el método de pago.
   - **Estado del pedido al verificarse el pago**: Estado en el que se queda el pedido tras verificarse el pago y crearse la factura.
   - **Mantener pedido si se produce un error**: Habilitar/Deshabilitar mantener los productos del carrito en caso de que falle el pago.
   - **Habilitar logs**: Habilitar/Deshabilitar los logs.

   #### Configuración Google Pay:
   - **Entorno de Google**: Indicar modo Producción o Prueba del entorno de GPay.
   - **Id comerciante Google**: Codigo de mercante indicado por Google tras realizar todos los pasos de la seccion de Requisitos.
   - **Tarjetas permitidas**: Elegir las tarjetas permitidas por la pasarela de pago. Para Redsys serian estas tarjetas (AMEX, DISCOVER, JCB, MASTERCARD, VISA)

   #### Configuración Pasarela:
   - **Pasarela**: Indicar pasarela de pago.
   - **Entorno de Redsys**: Indicar modo Producción o Prueba del entorno de Redsys.
   - **Nombre del comercio**: Indicar nombre del comercio.
   - **Url del comercio**: Indicar url del comercio.
   - **Número del comercio o FUC**: Número de comercio proporcionado por Redsys.
   - **Número de terminal**: Identificador de terminal asignado por Redsys.
   - **Clave de Encriptación SHA-256**: Clave de encriptación indicada por Redsys.
   - **Tipo de transacción**: El tipo de transaccion, en este caso para GPay seria "Autorización".
   - **Método de generación del número de pedido**: Como se genera el numero de pedido que se enviara a Redsys para identificar la operacion en el portal del TPV. Se recomienda el modo "Híbrido".
   - **El terminal permite número de pedido extendido**: Habilitar/Deshabilitar numeros de pedidos extendidos. Esto es útil para tiendas cuyos número de pedidos podrían exceder las doce posiciones que tiene como máximo un número de pedido estándar.

5. Guarda la configuración.

![Configuración Google Pay](https://drive.google.com/uc?export=view&id=1LUCjeOF3-0gA092rUQ_IL1_5IxUkNmDI)

![Configuración Pasarela](https://drive.google.com/uc?export=view&id=15LgZg1zG0D9VD6cP1PlqfcW6SOCgoVwb)

## Uso

Una vez configurado, Google Pay aparecerá como una opción de pago en la página de checkout. Los clientes podrán seleccionar dicho método, y tras seleccionar la tarjeta de su cuenta el pago se procesará directamente sin pasar por la pasarela tradicional de Redsys como punto intermedio del proceso. 

## Notas Importantes

- Este plugin no sustituye al plugin oficial de Redsys ni ninguna de sus opciones o distintos métodos, sino que lo complementa.
- Para usar Google Pay, debes cumplir con los requisitos de Redsys y tener una cuenta activa que permita el uso de esta funcionalidad.
- No se manejan datos sensibles de las tarjetas directamente, ya que la validación es realizada por Google. Por lo que no se requiere ser PCI.

## Compatibilidad

- Compatible con Magento 2.4.x

## Licencia

Este proyecto está licenciado bajo la Licencia Pública General de GNU v3.0 (GPL-3.0).
Puedes consultar el archivo [LICENSE](./LICENSE) para más detalles.

___

# Magento 2 Redsys Google Pay Plugin (English)

This module complements the [official Redsys plugin](https://pagosonline.redsys.es/descargas.html) for Magento 2, allowing the integration of Google Pay as an additional payment method. Through this plugin, customers can use Google Pay directly from the checkout page without the need to go through the Redsys payment gateway.

This corresponds to the [Direct Redsys integration model with Google Pay](https://pagosonline.redsys.es/googlePay.html#integraciondirecta).

## Requirements

- **Official Redsys Plugin**: This plugin requires that the official Redsys plugin is installed and configured on your Magento 2 store.
- **Account with Redsys and Google Pay**: To use this module, you must have an account with Redsys and meet their requirements for integration with Google Pay. Specifically, they must activate Google Pay payment for integration.
- **Google Business Account**: You must create a [Google Pay & Wallet account](https://pay.google.com/business/console) and configure and validate an integration with your store's domain. [Follow this guide for the web mode](https://developers.google.com/pay/api/web/overview) considering the [brand guidelines to pass the validation](https://developers.google.com/pay/api/web/guides/brand-guidelines) and [request production deployment when everything is ready and tested](https://developers.google.com/pay/api/web/guides/test-and-deploy/publish-your-integration).

## Important Considerations

We will not explain here how to configure or start working with Redsys, nor the complete process to create the integration with Google, as Redsys already provides guides. However, we will highlight some key points to consider:

- **You must have this type of payment activated with your bank**: Specifically, Google Pay with integration.
- **You must have the MIXED mode activated in the account**: The mixed mode enables non-secure operations against the Redsys gateway, meaning operations where Redsys does not need to validate the cards, as Google will do this when registering the cards in GPay. You must request this configuration from the bank.
- **Difference between tokenized operations for Apps and non-tokenized operations for web**: In non-tokenized card operations, Google encrypts the card in the message that the merchant later sends to Redsys. This operation must be authenticated, and that's where MIXED mode is necessary.
- **In the Redsys test environment, you cannot test the full payment cycle**: The TEST environment of Redsys payment gateway is not fully prepared to simulate an order with Google Pay integration, and no notification is sent to the merchant, so you will have to trust and test the full process in the REAL environment.

## Features

- **Direct integration with Google Pay**: Allows customers to pay with Google Pay directly from the checkout, without redirecting them to the Redsys gateway.
- **No PCI compliance required**: Since Google validates the cards, it is not necessary to manage card data directly or comply with PCI regulations.
- **Easy configuration**: Configure the plugin easily from the Magento admin panel, adding the necessary parameters to communicate with Redsys and Google's service.

## Installation

1. Make sure the official Redsys plugin is installed and correctly configured on your Magento 2 store.
2. Install this plugin:

    #### Using composer

    You can download the plugin directly as it is registered on packagist.org

    ```bash
    composer require omitsis/redsys-googlepay
    ```

    #### By direct file copy

    * Download the extension
    * Unzip the file
    * Create the directory app/code/Omitsis/RedsysGooglePay  
    * Copy the content of the file to that folder

3. Enable the module by running the following commands:

    ```bash
    bin/magento module:enable Omitsis_RedsysGooglePay
    bin/magento setup:upgrade
    bin/magento cache:clean
    ```

4. Configure the plugin from the admin panel.

## Configuration

1. Go to **Stores > Configuration > Sales > Payment Methods** in your Magento admin panel.

2. Find the "Redsys Pago con Google Pay" section.

3. Fill in the required fields; the configuration is divided into three tabs:
   #### General Configuration:
   - **Enable**: Enable/Disable the payment method.
   - **Order status after payment is verified**: The status of the order after the payment is verified and the invoice is created.
   - **Keep order if an error occurs**: Enable/Disable keeping the cart items in case the payment fails.
   - **Enable logs**: Enable/Disable logs.

   #### Google Pay Configuration:
   - **Google Environment**: Indicate Production or Test mode for the GPay environment.
   - **Google Merchant ID**: Merchant code provided by Google after completing the steps in the Requirements section.
   - **Allowed Cards**: Choose the cards allowed by the payment gateway. For Redsys, these cards are (AMEX, DISCOVER, JCB, MASTERCARD, VISA).

   #### Gateway Configuration:
   - **Gateway**: Indicate the payment gateway.
   - **Redsys Environment**: Indicate Production or Test mode for the Redsys environment.
   - **Merchant name**: Specify the merchant name.
   - **Merchant URL**: Specify the merchant URL.
   - **Merchant number or FUC**: Merchant number provided by Redsys.
   - **Terminal number**: Terminal identifier assigned by Redsys.
   - **SHA-256 Encryption Key**: Encryption key provided by Redsys.
   - **Transaction type**: The transaction type, in this case for GPay, would be "Authorization."
   - **Order number generation method**: How the order number sent to Redsys to identify the operation on the TPV portal is generated. "Hybrid" mode is recommended.
   - **Terminal allows extended order number**: Enable/Disable extended order numbers. This is useful for stores whose order numbers might exceed the twelve positions that a standard order number can have at most.

4. Save the configuration.

## Usage

Once configured, Google Pay will appear as a payment option on the checkout page. Customers can select this method, and after choosing the card from their account, the payment will be processed directly without going through the traditional Redsys gateway as an intermediary.

## Important Notes

- This plugin does not replace the official Redsys plugin or any of its options or different methods but complements it.
- To use Google Pay, you must meet Redsys's requirements and have an active account that allows the use of this functionality.
- No sensitive card data is handled directly, as validation is performed by Google. Therefore, PCI compliance is not required.

## Compatibility

- Compatible with Magento 2.4.x

## License

This project is licensed under the GNU General Public License v3.0 (GPL-3.0).
You can check the [LICENSE](./LICENSE) file for more details.
