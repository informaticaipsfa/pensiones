<?php

if (!defined('E_STRICT')) {
    define('E_STRICT', 2048);
}

/// Solo aplicar si estamos en PHP 8.2 o superior
if (PHP_VERSION_ID >= 80200) {
    // Esta función se ejecutará después de que CodeIgniter haya cargado sus clases
    function fix_ci_uri_deprecation() {
        if (class_exists('CI_URI', false)) {
            // Crear una nueva clase con AllowDynamicProperties
            // eval('
            //     #[AllowDynamicProperties]
            //     class CI_URI_Fixed extends CI_URI {}
            // ');
            
            // // Reemplazar la clase original
            // class_alias('CI_URI_Fixed', 'CI_URI');
        }
    }
    
    // Registrar la función para ejecutarse después de la carga de CI
    register_shutdown_function('fix_ci_uri_deprecation');
}