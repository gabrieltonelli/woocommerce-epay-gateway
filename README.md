# woocommerce-epay-gateway
Cobrá con todas las tarjetas del mercado con las comisiones más bajas.

Este plugin, para Wordpress, requiere Woocommerce previamente instalado y activado.
Agrega un nueva forma de pago, para abonar por E-pay, companía que ofrece pagos en línea por medio de POSNET.

Para el frontend, se ha utilizado la librería javascript de Stripe (https://js.stripe.com/v2/).

La información de pago, se envía a una capa intermedia, al gateway ubicado en http://manager.epaypago.com/, el cual valida del lado del servidor si el cliente está habilidado o no a usar el servicio.

Dicha capa intermedia es la que resuelve la lógica de comunicación con el gateway de FirstData mediante el método Connect, según la documentación de esta empresa.

La respuesta es retornada al frontend para comunicar por la transacción realizada con éxito o no.

También se consume una API REST de BIM para detectar a cuál entidad financiera y banco pertenece la tarjeta ingresada (a través de sus primeros 6 números). De esta forma se logra recuperar datos de promociones vigentes.

Desde la capa intermedia también se configuran las cuotas habilitadas con sus respectivas comisiones por pago en cuotas. Todo ello a nivel, entidad financiera (VISA, MASTER, AMEX, etc.) / banco / ecommerce.
