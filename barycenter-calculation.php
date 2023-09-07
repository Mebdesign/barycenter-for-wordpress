<?php
/*
Plugin Name: Barycenter Calculation
Plugin URI: https://mebdesign.fr/barycentre
Description: A simple barycentre calculator plugin for WordPress.
Version: 1.0
Author: Mebdesign.fr
Author URI: https://mebdesign.fr
License: GPL2
*/

// Includes functions.php
require_once plugin_dir_path( __FILE__ ) . 'functions.php';

function barycenter_style() {
    wp_enqueue_style( 'barycenter-style',  plugins_url('assets/css/style.css',__FILE__ ), false );
}
add_action( 'wp_enqueue_scripts', 'barycenter_style' );

function barycenter_script() {
    wp_enqueue_script( 'barycenter-js', plugins_url('assets/js/script.js',__FILE__), array('jquery', 'osm-map-js', 'osm-cluster-map-js'), '1.0', false );
    // Transmettez des données à ce script
    wp_localize_script('barycenter-js', 'barycenterParamsEmail', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('my-nonce')
    ));
}
add_action( 'wp_enqueue_scripts', 'barycenter_script' );

function enqueue_leaflet_pulse_icon() {
    wp_enqueue_style('leaflet-pulse-icon-css', plugins_url('assets/css/L.Icon.Pulse.css',__FILE__));
    wp_enqueue_script('leaflet-pulse-icon-js', plugins_url('assets/js/L.Icon.Pulse.js',__FILE__), array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_leaflet_pulse_icon');
