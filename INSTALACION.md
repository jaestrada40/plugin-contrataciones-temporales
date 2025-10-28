# 🚀 Instalación del Plugin Vacantes MINFIN

## ✅ Requisitos del Sistema

- **WordPress:** 5.0 o superior
- **PHP:** 7.4 o superior  
- **MySQL:** 5.6 o superior
- **Memoria PHP:** Mínimo 128MB
- **Permisos:** Lectura/escritura en directorio de plugins

## 📋 Pasos de Instalación

### 1. Preparar Archivos
```bash
# Verificar que todos los archivos estén presentes
php verificar-plugin.php
```

### 2. Subir al Servidor
- Subir toda la carpeta `wordpress-plugin` a `/wp-content/plugins/`
- Renombrar la carpeta a `vacantes-minfin`
- Verificar permisos (755 para directorios, 644 para archivos)

### 3. Activar Plugin
1. Ir a **WordPress Admin > Plugins**
2. Buscar "Vacantes MINFIN"
3. Hacer clic en **"Activar"**

### 4. Verificar Instalación
Después de activar, verificar que:
- ✅ Aparezca el menú "Vacantes" en el admin
- ✅ Se hayan creado las tablas de base de datos
- ✅ No haya errores en el log de WordPress

### 5. Configuración Inicial
1. Ir a **Vacantes > Configuración**
2. Configurar:
   - Email del administrador
   - Tamaño máximo de archivos
   - Tipos de archivo permitidos
   - Configuraciones de API (si aplica)

## 🗄️ Tablas de Base de Datos

El plugin creará automáticamente estas tablas:

```sql
wp_direcciones_minfin          # Direcciones organizacionales
wp_tipos_contrato_minfin       # Tipos de contrato
wp_vacantes_minfin            # Vacantes laborales  
wp_aplicaciones_minfin        # Aplicaciones de candidatos
```

## 🎯 Configuración Post-Instalación

### Crear Páginas Públicas
El plugin crea automáticamente:
- `/vacantes/` - Lista de vacantes públicas
- `/aplicar-vacante/` - Formulario de aplicación

### Configurar Shortcodes
```php
[vacantes_lista limite="10"]           # Lista de vacantes
[vacante_detalle id="123"]            # Detalle de vacante
[vacantes_formulario]                 # Formulario de aplicación
[vacantes_buscar]                     # Buscador de vacantes
```

### Configurar Widgets
1. Ir a **Apariencia > Widgets**
2. Agregar widgets disponibles:
   - **Vacantes Widget** - Lista de vacantes destacadas
   - **Stats Widget** - Estadísticas del sistema

## 🔧 Solución de Problemas

### Error de Activación
```bash
# Verificar sintaxis PHP
php -l vacantes-minfin.php

# Verificar permisos
chmod 755 /wp-content/plugins/vacantes-minfin/
chmod 644 /wp-content/plugins/vacantes-minfin/*.php
```

### Tablas No Se Crean
1. Verificar permisos de base de datos
2. Revisar logs de WordPress
3. Desactivar y reactivar el plugin

### Archivos No Se Suben
1. Verificar permisos del directorio `wp-content/uploads/`
2. Aumentar `upload_max_filesize` en PHP
3. Verificar configuración en **Vacantes > Configuración**

## 📞 Soporte

Si encuentras problemas:
1. Revisar el archivo `TROUBLESHOOTING.md`
2. Verificar logs de WordPress en `/wp-content/debug.log`
3. Contactar soporte técnico: soporte@minfin.gob.gt

## 🔄 Actualización

Para actualizar el plugin:
1. **Hacer backup** de la base de datos
2. Desactivar el plugin actual
3. Subir los nuevos archivos
4. Reactivar el plugin
5. Verificar que todo funcione correctamente

---

**¡Plugin listo para usar!** 🎉

Para más información, consultar `DOCUMENTACION-COMPLETA.md`