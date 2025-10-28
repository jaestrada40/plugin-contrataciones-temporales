=== Vacantes MINFIN ===
Contributors: minfin
Tags: jobs, vacancies, employment, government, applications
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo de gestión de vacantes laborales para el Ministerio de Finanzas Públicas de Guatemala.

== Description ==

El plugin Vacantes MINFIN es un sistema completo de gestión de vacantes laborales diseñado específicamente para el Ministerio de Finanzas Públicas de Guatemala. 

**Características principales:**

* **Panel administrativo completo** - Gestión de vacantes, aplicaciones, direcciones y tipos de contrato
* **Formularios públicos** - Los ciudadanos pueden aplicar a vacantes desde el sitio web
* **Sistema de archivos** - Carga segura de CVs y documentos
* **Notificaciones automáticas** - Emails de confirmación y notificaciones a administradores
* **Reportes y estadísticas** - Dashboard con métricas en tiempo real
* **API REST** - Integración con otros sistemas
* **Widgets configurables** - Para mostrar vacantes en sidebars
* **Shortcodes** - Para insertar funcionalidad en páginas y posts
* **Responsive design** - Compatible con dispositivos móviles

**Shortcodes disponibles:**

* `[vacantes_lista]` - Muestra lista de vacantes con filtros
* `[vacantes_aplicar vacante_id="123"]` - Formulario de aplicación
* `[vacantes_buscar]` - Buscador de vacantes

**Para administradores:**

* Gestión completa de vacantes (crear, editar, eliminar)
* Revisión de aplicaciones con cambio de estados
* Sistema de reportes con exportación
* Gestión de direcciones y tipos de contrato
* Dashboard con estadísticas en tiempo real
* Sistema de logs de auditoría

**Para usuarios públicos:**

* Búsqueda y filtrado de vacantes
* Aplicación en línea con carga de CV
* Notificaciones automáticas por email
* Interfaz responsive y accesible

== Installation ==

1. Sube la carpeta `vacantes-minfin` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Las tablas de base de datos se crean automáticamente
4. Ve a 'Vacantes' en el menú de administración para configurar
5. Usa los shortcodes en páginas y posts según necesites

== Frequently Asked Questions ==

= ¿Qué formatos de archivo acepta para CVs? =

El plugin acepta archivos PDF, DOC y DOCX con un tamaño máximo de 5MB por defecto.

= ¿Puedo personalizar los emails de notificación? =

Sí, los templates de email son completamente personalizables desde el panel de administración.

= ¿Es compatible con otros plugins? =

Sí, el plugin está diseñado para ser compatible con la mayoría de plugins de WordPress.

= ¿Puedo exportar los datos? =

Sí, el sistema incluye funcionalidad de exportación de reportes en formato CSV y PDF.

== Screenshots ==

1. Dashboard administrativo con estadísticas
2. Lista de vacantes en el frontend
3. Formulario de aplicación
4. Panel de gestión de aplicaciones
5. Configuración de direcciones

== Changelog ==

= 1.0.0 =
* Lanzamiento inicial
* Sistema completo de gestión de vacantes
* Panel administrativo con dashboard
* Formularios públicos de aplicación
* Sistema de notificaciones por email
* API REST para integraciones
* Widgets y shortcodes
* Sistema de reportes

== Upgrade Notice ==

= 1.0.0 =
Lanzamiento inicial del plugin. Instala para comenzar a gestionar vacantes laborales.

== Technical Requirements ==

* WordPress 5.0 o superior
* PHP 7.4 o superior
* MySQL 5.6 o superior
* Extensiones PHP: mysqli, json, mbstring
* Permisos de escritura en directorio uploads

== Support ==

Para soporte técnico, contacta al equipo de desarrollo del MINFIN:
* Email: soporte-vacantes@minfin.gob.gt
* Documentación: Incluida en el plugin

== Privacy Policy ==

Este plugin recopila y almacena información personal de los aplicantes (nombre, email, teléfono, CV) únicamente para fines de reclutamiento. Los datos se almacenan de forma segura y se procesan según las políticas de privacidad del MINFIN.