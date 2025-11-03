<?php

/**
 * Plugin Name: Mi Plugin Sencillo
 * Description: Un plugin para practicar con un panel de admin y un shortcode.
 * Version: 1.0
 * Author: Guillermo Ogallar Miranda
 */

function mi_plugin_shortcode() {
    return "¡Hola desde mi plugin sencillo!";
}
add_shortcode('mi_plugin', 'mi_plugin_shortcode');
