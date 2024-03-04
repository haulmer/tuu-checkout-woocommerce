tuu-checkout - WooCommerce
=========
- SitioWeb: ...
- Comunidad: [Chat Slack](https://communityinviter.com/apps/haulmer/haulmer)
- Documentación: [API Rest](http://)
- Tutorial de instalación: [Haulmer Blog - Tutorial](https://)

<img alt="tuu-checkout-WooCommerce" src="https://t3.ftcdn.net/jpg/02/22/01/40/360_F_222014094_jh9mcZTHzsw71AkhefOEGyQH36EjjPLJ.jpg" width="600px">




Los beneficios del plugin ---- son:

- **texto 1**: 

Funcionamiento del plugin
-------------------------------

Todo el funcionamiento del plugin radica en el hook de WooCommerce `woocommerce_order_status_completed`, cuando este hook es gatillado comienzan a realizarse las siguientes rutinas:

 1. Genera y realiza el envío de la información a OpenFactura para la generación del DTE.
 2. Cuando llega la respuesta desde OpenFactura, se almacena en las notas de la orden; el tipo de documento, folio y el link de autoservicio.
 3. Se completa información de los meta `_invoice_serial`, `_document_code`, `_document_type`. De utilidad para otras integraciones dentro de WooCommerce.

Instalación plugin
-------------------------------

La documentación está disponible en nuestro sitio de tutoriales [Haulmer  Docs](https://help.haulmer.com/):
  - [Guía de Instalación del plugin woocommerce](https://help.haulmer.com/hc/integraciones/como-instalar-el-plugin-de-openfactura-en-woocommerce-8f03230a-9bc5-4892-8e04-590c1618593a)
