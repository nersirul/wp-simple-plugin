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
    // 1. Registrar la opción para ESPAÑA
    register_setting(
        'wpsp_grupo_opciones',          // Grupo de opciones
        'wpsp_texto_es',                // Nombre de la opción para ES
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        )
    );

    // 2. Registrar la opción POR DEFECTO
    register_setting(
        'wpsp_grupo_opciones',          // Grupo de opciones
        'wpsp_texto_default',           // Nombre de la opción para el resto
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        )
    );

    // Añadimos una sección a nuestra página de ajustes
    add_settings_section(
        'wpsp_seccion_principal',          // ID de la sección
        'Ajustes Principales',             // Título de la sección
        'wpsp_seccion_principal_callback',     // Función que muestra la descripción de la sección
        'wpsp-plugin-opciones'             // Página donde se mostrará la sección
    );

    // Añadimos el CAMPO 1 (para ES)
    add_settings_field(
        'wpsp_campo_texto_es',
        'Texto para España (ES)',
        'wpsp_campo_texto_es_callback', // Nuevo callback
        'wpsp-plugin-opciones',
        'wpsp_seccion_principal'
    );

    // Añadimos el CAMPO 2 (por Defecto)
    add_settings_field(
        'wpsp_campo_texto_default',
        'Texto por Defecto (Resto del mundo)',
        'wpsp_campo_texto_default_callback', // Nuevo callback
        'wpsp-plugin-opciones',
        'wpsp_seccion_principal'
    );
}

/**
 * 3. Funciones "callback" para mostrar el html
 * 
 * Funciones referenciadas en los pasos anteriores.
 */

// Callback para la descripción de la sección
function wpsp_seccion_principal_callback()
{
    echo '<p>Introduce el texto que se mostrará según la ubicación del visitante.</p>';
}

// NUEVO: Callback para el campo de ESPAÑA
function wpsp_campo_texto_es_callback()
{
    $valor_guardado = get_option('wpsp_texto_es');
    printf(
        '<input type="text" id="wpsp_texto_es" name="wpsp_texto_es" value="%s" class="regular-text" />',
        esc_attr($valor_guardado)
    );
}

// NUEVO: Callback para el campo por DEFECTO
function wpsp_campo_texto_default_callback()
{
    $valor_guardado = get_option('wpsp_texto_default');
    printf(
        '<input type="text" id="wpsp_texto_default" name="wpsp_texto_default" value="%s" class="regular-text" />',
        esc_attr($valor_guardado)
    );
}

/**
 * 4. Función que muestra todo el HTML en la página de opciones.
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
 * 5. Registramos el shortcode
 * 
 * Usamos elhook 'init' para registrar el shortcode.
 */
add_action( 'init', 'wpsp_registrar_shortcode' );

function wpsp_registrar_shortcode() {
    // Registramos el shortcode [mi_texto_sencillo] y le decimos qué función usar
    add_shortcode('mi_texto_sencillo', 'wpsp_shortcode_callback' );
}

function wpsp_shortcode_callback() {
    // Obtenemos la IP del visitante. 
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Limpiamos la IP por si vienen varias: 
    $ip = explode(',', $ip)[0];
    $ip = trim($ip);

    if ($ip == '127.0.0.1' || $ip == '::1'){
        $ip = '81.38.86.11';
    }

    // Comprobamos la cache
    $transient_key = 'wpsp_geo_' . md5( $ip );; // Clave única para la IP
    $country_code = get_transient( $transient_key ); // Intentamos obtener el código de país de la cache

    if (false === $country_code){
        $ulr = 'http://ip-api.com/json/' . $ip;
        $response = wp_remote_get( $ulr );
        $country_code = 'DEFAULT'; // Valor por defecto

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200){
            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );
            if ($data && data->status === 'success' && !empty($data->countryCode)){
                $country_code = $data->countryCode;
            }
        }

        // Guardamos en la cache por 6 horas
        set_transient( $transient_key, $country_code, 6 * HOUR_IN_SECONDS );
    }

    // Decidimos ahora que texto mosatramos
    $texto_a_mostrar = '';
    if ( $country_code === 'ES' ) {
        $texto_a_mostrar = get_option( 'wpsp_texto_es', 'Texto por defecto para España' );
    } else {
        $texto_a_mostrar = get_option( 'wpsp_texto_default', 'Texto por defecto para el resto del mundo' );
    }
    if (empty($texto_a_mostrar)) {
        $texto_a_mostrar = get_option('wpsp_texto_default');
    }

    return esc_html( $texto_a_mostrar );
}