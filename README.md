# Pasarela de pagos Banorte Comercio Electrónico Payworks 2.0 y 3D Secure para Magento 2 / Adobe Commerce por SIXPLUS1

    ``sixplus1/banorte``

 - [Funciones Principales](#markdown-header-main-functionalities)
 - [Instalación](#markdown-header-installation)
 - [Especificaciones](#markdown-header-specifications)


## Funciones Principales
Este módulo es la versión Open Source de nuestra integración a Comercio Electrónico de Banorte Payworks 2.0. Este módulo le permitirá integrar su sitio Magento 2 / Adobe Commerce a la pasarela de pagos de Banorte. Recuerde que antes de salir a producción es necesario que se ponga en contacto con el soporte técnico de Banorte para que lleve a cabo el proceso de certificación.

- **VERSION PRO DISPONIBLE:** Si tiene alguna duda o desea contratar el servicio de instalación, personalización, certificación o bien usar la versión PRO de este módulo que incluye la compatiblidad con las úiltimas versiones de Adobe Commerce / Magento 2, integración a Cybersource de Banorte, personalización de MDD's, seguridad mejorada, uso de concentración empresrial de pagos de Banorte (CEP), auditoría del sitio y certificación con Banorte escribanos a [contacto@sixplus1.com](mailto:contacto@sixplus1.com) .
- **SI SU APLICAION NO ES MAGENTO:** Si su sistema NO está basado en Magento 2/Adobe Commerce en SIXPLUS1 también tenemos integraciones para todo tipo de aplicaciones y desarrollos, encuentrenos en [https://www.sixplus1.com/](https://www.sixplus1.com/)
- **3D Secure:** Todas las transacciones de este modulo Open Source usan el motor 3DSecure provisto por Banorte

**IMPORTANTE: Esta integración de Magento 2 / Adobe Commerce Payworks 2 y 3D Secure por SIXPLUS1 (GRUPO SONET360 S.A. DE C.V.) es liberada de forma libre, gratuita y sin garantía de ningún tipo. Si usted integra y usa este módulo open source en su tienda Magento 2 / Adobe Commerce se hace completamente responsable de la operación, integración, resguardo, mantenimiento, actualización, seguridad, fallas, funcionamiento, certificación y uso del mismo. Ninguno de los programadores de SIXPLUS1 (GRUPO SONET360 S.A. DE C.V.) o GRUPO SONET360 S.A. DE C.V. asume alguna responsabilidad por el uso de este módulo. Ni tampoco si su comercio sale a producción usando este módulo sin haber llevado a cabo el proceso de certificación con Banorte y/o si su dominio no cuenta con una licencia para usar éste modulo por parte de SIXPLUS1**

Este módulo fue desarrollado por SIXPLUS1 solamente, sin ningúna cooperación o relación contractual, comercial o de ningún tipo con el banco Banorte. Los lineamientos y la documentación utilizada para hacer la conexión con Banorte fue actualizada en Mayo de 2023.

Si necesita integrar su sistema a BBVA MÉXICO MULTIPAGOS, BBVA AUTOMATA, BANAMEX, SANTANDER o FISERV MÉXICO escribanos a  [contacto@sixplus1.com](mailto:contacto@sixplus1.com) o ingrese a nuestro sitio web [https://www.sixplus1.com/](https://www.sixplus1.com/)

## Instalación
En producción usar la opcion `--keep-generated`

### Instalación Tipo 1: Archivo Zip

 - Descargar y descomprimir el repositorio en formato zip en `app/code/Sixplus1`
 - Habilitar el modulo corriendo `php bin/magento module:enable Sixplus1_Banorte`
 - Aplicar las actualizaciones a base de datos corriendo `php bin/magento setup:upgrade`\*
 - Compilar Magento corriendo `php bin/magento setup:di:compile`\*
 - Limpiar la cache corriendo `php bin/magento cache:flush`

### Instalación Tipo 2 (Recomendada): Usando Composer 

 - Instalar el modulo usando composer corriendo `composer require sixplus1/banorte`
 - Habilitar el modulo corriendo `php bin/magento module:enable Sixplus1_Banorte`
 - Aplicar las actualizaciones a base de datos corriendo `php bin/magento setup:upgrade`\*
 - Compilar Magento corriendo `php bin/magento setup:di:compile`\*
 - Limpiar la cache corriendo `php bin/magento cache:flush`

## Especificaciones

 - En la configuración de Adobe Commerce / Magento 2 vaya a Métodos de Pago.
	- Busque la opción Sixplus1 Banorte, personalize el módulo e ingrese los siguientes datos obligatorios que le son proporcionados por su ejecutivo de Banorte para que el módulo funcione correctamente:
        - **Nombre del Comercio (Tienda):** Puede usar los datos de su afiliación.
        - **Ciudad Matriz del Comercio:** Ciudad en México en donde se encuentra la sucursal en donde realizó la afiliación.
        - **ID Afiliación:** Número de Afiliación de Payworks.
        - **Usuario Payworks:** Usuario de Payworks.
        - **Clave (Password) Payworks:** La clave o contraseña que dio de alta en Payworks.
        - **ID Terminal:** El ID de la terminal que le debe proporcionar su ejecutivo.



## Licencia

[Open Source License](LICENSE.txt)

