<?php

/**
 * Plugin Name: Mi Plugin Sencillo
 * Description: Un plugin para practicar con un panel de admin y un shortcode.
 * Version: 1.0
 * Author: Guillermo Ogallar Miranda
 */

// EVitar el acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente
}

/**
 * 1. Añadir un menú de administración
 * 
 * Usamos el 'hook' 'admin_menu' para añadir un nuevo menú en el panel de administración de WordPress.
 */
add_action('admin_menu', 'wpsp_crear_menu_admin');

// Función que crea el menú de administración
function wpsp_crear_menu_admin() {
    add_options_page(
        'Ajustes de Mi Plugin',      // Título de la página
        'Mi pLugin Sencillo',        // Título que se verá en el menú
        'manage_options',            // Capacidad requerida para verla (solo admins)
        'wpsp-plugin-opciones',      //'slug' único para la página
        'wpsp_pagina_opciones_html'  // Función que muestra el contenido de la página
    );
}

/**
 * 2. Registrar los ajustes
 *
 * Usamos el 'hook' 'admin_init' para registrar los ajustes del plugin.
 */
add_action( 'admin_init', 'wpsp_registrar_ajustes' );

function wpsp_registrar_ajustes() {
    // Registramos el ajuste
    register_setting(
        'wpsp_grupo_opciones', // Nombre del grupo de opciones
        'wpsp_texto_guardado', // Nombre de la opción
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field', // Función de WordPress para limpiar el valor
            'default' => '',
        )
    );

    // Añadimos una sección a nuestra página de ajustes
    add_settings_section(
        'wpsp_seccion_principal',          // ID de la sección
        'Ajustes Principales',             // Título de la sección
        'wpsp_seccion_principal_callback',     // Función que muestra la descripción de la sección
        'wpsp-plugin-opciones'             // Página donde se mostrará la sección
    );

    // Añadimos el campo de texto a la sección que acabamos de crear
    add_settings_field(
        'wpsp_campo_texto',                // ID del campo
        'Texto Guardado',                  // Título del campo
        'wpsp_campo_texto_callback',       // Función que muestra el campo
        'wpsp-plugin-opciones',            // Página donde se mostrará el campo
        'wpsp_seccion_principal'           // Sección donde se mostrará el campo
    );
}

/**
 * 3. Funciones "callback" para mostrar el html
 * 
 * Funciones referenciadas en los pasos anteriores.
 */

// Callback para la descripción de la sección
function wpsp_seccion_principal_callback() {
    echo '<p>Introduce el texto que quiers mostrar en la parte pública</p>';
}

// Callback para mostrar el campo de texto
function wpsp_campo_texto_callback() {
    // Obtenemos el valor
    $valor = get_option( 'wpsp_texto_guardado', '' );
    // Mostramos el valor en el campo de texto
    echo '<input type="text" id="wpsp_texto_guardado" name="wpsp_texto_guardado" value="' . esc_attr( $valor ) . '" />';
}

/**
 * Función que muestra todo el HTML en la página de opciones.
 * Es la función que registramos en 'add_options_page' en el paso 1.
 */
function wpsp_pagina_opciones_html() {
    // Verificamos que el usuario tiene permisos
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Mostramos el formulario de ajustes
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Función MUY IMPORTANTE:
            // 1. Muestra campos ocultos de seguridad (nonces, referers...)
            // 2. Le dice a qué grupo de opciones pertenece este formulario
            settings_fields('wpsp_grupo_opciones');

            // Función IMPORTANTE:
            // Muestra todas las secciones y campos que hemos registrado
            // para esta página ('wpsp-plugin-opciones')
            do_settings_sections('wpsp-plugin-opciones');

            // Muestra el botón de guardar
            submit_button('Guardar Cambios');
            ?>
        </form>
    </div>
    <?php
}

/**
 * 4. Registramos el shortcode
 * 
 * Usamos elhook 'init' para registrar el shortcode.
 */
add_action( 'init', 'wpsp_registrar_shortcode' );

function wpsp_registrar_shortcode() {
    // Registramos el shortcode [mi_texto_sencillo] y le decimos qué función usar
    add_shortcode('mi_texto_sencillo', 'wpsp_shortcode_callback' );
}

function wpsp_shortcode_callback() {
    // Obtenemos el valor guardado en la opción
    $texto = get_option( 'wpsp_texto_guardado', '' );

    // Devolvemos el texto para que se muestre donde se use el shortcode
    return esc_html( $texto );
}