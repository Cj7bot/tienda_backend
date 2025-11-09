# Configuración de Mailtrap para Envío de Comprobantes de Pago

## Descripción
Este sistema utiliza Mailtrap para enviar comprobantes de pago por correo electrónico cuando se procesa un pedido.

## Componentes Agregados

### 1. Servicio de Email (`MailtrapEmailService`)
- **Ubicación**: `src/Service/MailtrapEmailService.php`
- **Función**: Gestiona el envío de comprobantes de pago por email usando Mailtrap
- **Características**:
  - Genera PDF del comprobante automáticamente
  - Crea un email HTML profesional con los detalles del pedido
  - Adjunta el PDF al correo
  - Maneja errores sin interrumpir el flujo del pedido

### 2. Integración en OrderController
- El servicio se integra automáticamente cuando se procesa un pedido
- No afecta el flujo existente del sistema
- Los errores de envío se registran pero no detienen la creación del pedido

### 3. Comando de Prueba
- **Comando**: `php bin/console app:test-mailtrap`
- **Función**: Envía un email de prueba para verificar la configuración

## Configuración

### Paso 1: Obtener API Key de Mailtrap

1. Ve a [Mailtrap.io](https://mailtrap.io)
2. Inicia sesión o crea una cuenta
3. Ve a **Email Sending** > **Sending Domains** (no uses el Inbox de testing)
4. Crea un dominio o usa uno existente
5. Ve a **API Tokens** y copia tu API Token

### Paso 2: Configurar Variables de Entorno

Edita tu archivo `.env` y agrega/modifica las siguientes líneas:

```env
###> symfony/mailer ###
MAILER_DSN=mailtrap+api://YOUR_API_TOKEN@default
###< symfony/mailer ###
```

Reemplaza `YOUR_API_TOKEN` con tu token de Mailtrap.

### Paso 3: Verificar Configuración

Ejecuta el comando de prueba:

```bash
php bin/console app:test-mailtrap
```

Si todo está configurado correctamente, verás un mensaje de éxito y el email aparecerá en tu bandeja de Mailtrap.

## Uso

### Envío Automático
El sistema envía automáticamente un comprobante de pago cuando:
- Se procesa un pedido exitosamente en `/api/checkout/process-order`
- El email se envía al correo del cliente registrado

### Personalización

#### Cambiar el remitente
Edita `src/Service/MailtrapEmailService.php`, línea ~42:

```php
->from('ventas@pureinkafoods.com')
```

#### Personalizar el HTML del email
Modifica el método `generateEmailHtml()` en `src/Service/MailtrapEmailService.php`

#### Cambiar la plantilla del PDF
Edita `templates/invoice/invoice.html.twig`

## Logs

Los eventos de envío de email se registran en:
- `var/log/dev.log` (desarrollo)
- `var/log/prod.log` (producción)

Busca líneas con:
- "Generando PDF para el pedido"
- "Comprobante de pago enviado con éxito"
- "Error al generar o enviar comprobante"

## Solución de Problemas

### El email no se envía
1. Verifica que `MAILER_DSN` esté correctamente configurado en `.env`
2. Revisa los logs en `var/log/`
3. Ejecuta el comando de prueba: `php bin/console app:test-mailtrap`

### Error de API Token
- Asegúrate de usar un token de **Email Sending**, no de **Email Testing**
- Verifica que el token no tenga espacios adicionales

### PDF no se genera
- Verifica que `wkhtmltopdf` esté instalado: `which wkhtmltopdf`
- Revisa la configuración de KnpSnappyBundle en `config/packages/knp_snappy.yaml`

## Diferencias con el Sistema Anterior

- **Antes**: Se usaba `MailerInterface` directamente en el controlador
- **Ahora**: Se usa `MailtrapEmailService` que encapsula toda la lógica
- **Ventajas**:
  - Código más limpio y mantenible
  - Fácil de probar
  - Reutilizable en otros controladores
  - Mejor manejo de errores

## Notas Importantes

⚠️ **El sistema NO se ve afectado si el envío de email falla**. El pedido se procesa correctamente y solo se registra el error en los logs.

✅ **No se modificó ninguna funcionalidad existente**. Solo se agregó el componente de envío de emails.

🔒 **Seguridad**: Nunca subas tu `.env` al repositorio. El API Token debe mantenerse privado.
