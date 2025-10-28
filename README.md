# Plugin WordPress - Vacantes MINFIN

## 📋 Descripción
Sistema completo de gestión de vacantes laborales para el Ministerio de Finanzas Públicas de Guatemala. Plugin de WordPress que permite administrar ofertas de trabajo, recibir aplicaciones y gestionar candidatos de forma eficiente.

## 🚀 Características Principales

### ✨ Funcionalidades Públicas
- 📋 **Listado de vacantes** con filtros avanzados
- 🔍 **Búsqueda inteligente** por términos y categorías
- 📝 **Formulario de aplicación** con validaciones
- 📎 **Carga de CV** (PDF, DOC, DOCX - máx. 5MB)
- 📱 **Diseño responsive** para móviles y tablets
- 🎯 **Shortcodes** para integración en páginas
- 🔌 **Widgets** para sidebar y footer

### 🛠️ Panel Administrativo
- 📊 **Dashboard** con estadísticas en tiempo real
- 📋 **Gestión completa de vacantes** (CRUD)
- 👥 **Administración de aplicaciones** con estados
- 🏢 **Gestión de direcciones** organizacionales
- ⚙️ **Configuración flexible** del sistema
- 📈 **Reportes** y estadísticas detalladas
- 📧 **Notificaciones automáticas** por email

### 🔧 Características Técnicas
- 🔒 **Seguridad robusta** con nonces y validaciones
- 🎨 **Interfaz moderna** con Bootstrap 5
- ⚡ **AJAX** para mejor experiencia de usuario
- 🔌 **API REST** para integraciones externas
- 📱 **Responsive design** completo
- 🌐 **Multiidioma** preparado

## 📁 Estructura del Plugin

```
wordpress-plugin/
├── vacantes-minfin.php              # 🎯 Archivo principal
├── admin/                           # 🛠️ Panel administrativo
│   ├── class-vacantes-admin.php     # Clase principal del admin
│   ├── pages/                       # Páginas del admin
│   │   ├── dashboard.php            # Dashboard principal
│   │   ├── vacantes-list.php        # Gestión de vacantes
│   │   ├── aplicaciones-list.php    # Gestión de aplicaciones
│   │   ├── direcciones-list.php     # Gestión de direcciones
│   │   └── config.php               # Configuración
│   ├── css/                         # Estilos del admin
│   └── js/                          # Scripts del admin
├── public/                          # 🌐 Funcionalidades públicas
│   ├── class-vacantes-public.php    # Clase principal pública
│   ├── partials/                    # Templates
│   ├── css/                         # Estilos públicos
│   └── js/                          # Scripts públicos
├── includes/                        # 🔧 Clases principales
│   ├── class-vacantes-core.php      # Núcleo del plugin
│   ├── class-vacantes-activator.php # Activación del plugin
│   ├── ajax-handlers.php            # Manejadores AJAX
│   └── shortcodes.php               # Shortcodes
├── models/                          # 📊 Modelos de datos
│   ├── class-vacante.php            # Modelo de vacantes
│   ├── class-aplicacion.php         # Modelo de aplicaciones
│   ├── class-direccion.php          # Modelo de direcciones
│   └── class-tipo-contrato.php      # Modelo de tipos de contrato
├── services/                        # 🔧 Servicios del sistema
│   ├── class-email-service.php      # Servicio de emails
│   ├── class-file-service.php       # Servicio de archivos
│   └── class-notification-service.php # Servicio de notificaciones
├── widgets/                         # 🎛️ Widgets de WordPress
│   ├── class-vacantes-widget.php    # Widget de vacantes
│   └── class-stats-widget.php       # Widget de estadísticas
├── api/                            # 🔌 API REST
│   └── class-rest-api.php          # Endpoints de la API
└── languages/                      # 🌐 Archivos de traducción
```

## 🛠️ Instalación

### Requisitos del Sistema
- **WordPress:** 5.0 o superior
- **PHP:** 7.4 o superior
- **MySQL:** 5.6 o superior
- **Memoria PHP:** Mínimo 128MB

### Pasos de Instalación

1. **Descargar el plugin**
   ```bash
   # Clonar o descargar los archivos del plugin
   ```

2. **Subir a WordPress**
   - Subir la carpeta `wordpress-plugin` a `/wp-content/plugins/`
   - Renombrar a `vacantes-minfin`

3. **Activar el plugin**
   - Ir a `Plugins > Plugins Instalados`
   - Activar "Vacantes MINFIN"

4. **Configuración inicial**
   - Ir a `Vacantes > Configuración`
   - Configurar URLs de API, emails, etc.

## 🎯 Uso del Plugin

### Shortcodes Disponibles

```php
// Lista de vacantes
[vacantes_lista limite="10" direccion="1"]

// Detalle de vacante específica
[vacante_detalle id="123"]

// Formulario de aplicación
[vacantes_formulario vacante_id="123"]

// Buscador de vacantes
[vacantes_buscar mostrar_resultados="si"]
```

### Widgets Disponibles

1. **Widget de Vacantes** - Muestra vacantes destacadas
2. **Widget de Estadísticas** - Muestra estadísticas del sistema

### API REST Endpoints

```
GET    /wp-json/vacantes/v1/vacantes          # Listar vacantes
GET    /wp-json/vacantes/v1/vacantes/{id}     # Obtener vacante
POST   /wp-json/vacantes/v1/aplicaciones      # Crear aplicación
GET    /wp-json/vacantes/v1/direcciones       # Listar direcciones
```

## ⚙️ Configuración

### Configuraciones Principales

1. **API Externa**
   - URL de la API del backend
   - Clave de autenticación
   - Timeout de conexión

2. **Notificaciones Email**
   - Habilitar/deshabilitar notificaciones
   - Email del administrador
   - Plantillas de email

3. **Archivos**
   - Tamaño máximo de archivos CV
   - Tipos de archivo permitidos
   - Directorio de almacenamiento

4. **Visualización**
   - Elementos por página
   - Mostrar/ocultar salarios
   - Campos obligatorios

## 🔧 Desarrollo

### Hooks Disponibles

```php
// Filtros
apply_filters('vacantes_minfin_vacante_data', $data);
apply_filters('vacantes_minfin_aplicacion_data', $data);
apply_filters('vacantes_minfin_email_template', $template);

// Acciones
do_action('vacantes_minfin_vacante_created', $vacante_id);
do_action('vacantes_minfin_aplicacion_received', $aplicacion_id);
do_action('vacantes_minfin_estado_changed', $aplicacion_id, $nuevo_estado);
```

### Extender Funcionalidades

```php
// Agregar campos personalizados
add_filter('vacantes_minfin_vacante_fields', function($fields) {
    $fields['campo_personalizado'] = 'Valor';
    return $fields;
});

// Personalizar emails
add_filter('vacantes_minfin_email_template', function($template, $tipo) {
    if ($tipo === 'nueva_aplicacion') {
        // Personalizar template
    }
    return $template;
}, 10, 2);
```

## 📊 Base de Datos

### Tablas Creadas

- `wp_vacantes_minfin` - Vacantes
- `wp_aplicaciones_minfin` - Aplicaciones
- `wp_direcciones_minfin` - Direcciones
- `wp_tipos_contrato_minfin` - Tipos de contrato

## 🔒 Seguridad

- ✅ Validación de nonces en todos los formularios
- ✅ Sanitización de datos de entrada
- ✅ Verificación de permisos de usuario
- ✅ Escape de datos de salida
- ✅ Validación de tipos de archivo
- ✅ Protección contra SQL injection
- ✅ Protección CSRF

## 🐛 Troubleshooting

### Problemas Comunes

1. **Plugin no se activa**
   - Verificar versión de PHP (mín. 7.4)
   - Verificar permisos de archivos

2. **Archivos no se suben**
   - Verificar permisos del directorio uploads
   - Verificar límites de PHP (upload_max_filesize)

3. **Emails no se envían**
   - Verificar configuración SMTP de WordPress
   - Verificar configuración del plugin

## 📝 Changelog

### Versión 2.0.0
- ✨ Versión inicial completa
- 🎯 Sistema completo de gestión de vacantes
- 📱 Interfaz responsive con Bootstrap 5
- 🔌 API REST completa
- 📧 Sistema de notificaciones
- 🛠️ Panel administrativo completo

## 👥 Soporte

Para soporte técnico o reportar bugs:
- 📧 Email: soporte@minfin.gob.gt
- 📋 Documentación: Ver archivos incluidos
- 🐛 Issues: Reportar en el repositorio

## 📄 Licencia

GPL v2 or later - Ministerio de Finanzas Públicas de Guatemala

---

**Desarrollado con ❤️ para el Ministerio de Finanzas Públicas de Guatemala**