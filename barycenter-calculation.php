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

function barycenter_enqueue_scripts() {
    wp_enqueue_script('barycenter-js', plugins_url('/assets/js/script.js', __FILE__), array('jquery', 'osm-map-js', 'osm-cluster-map-js'), '1.0', false);

    $barycenter_params = array(
        'latitude' => (float) get_option('barycenter_latitude'),
        'longitude' => (float) get_option('barycenter_longitude'),
        'zoom' => get_option('barycenter_zoom'),
        'product_id' => get_option('barycenter_product_id'),
        'redirect_url' => get_option('barycenter_redirect_url'),
        'hasPurchased' => has_user_purchased_product(get_current_user_id(), get_option('barycenter_product_id')),
        'timer' => get_option('barycenter_timer'),
        'enable_timer' => get_option('barycenter_enable_timer') === 'on' ? true : false,
        'enable_cluster' => get_option('barycenter_enable_cluster') === 'on' ? true : false,
        'limits' => get_option('barycenter_limits'),
        'option' =>  get_option('barycenter_color'),
        'ajax_url' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('my-nonce')
    );

    wp_localize_script('barycenter-js', 'barycenterParams', $barycenter_params);
}
add_action('wp_enqueue_scripts', 'barycenter_enqueue_scripts');


function enqueue_leaflet_pulse_icon() {
    wp_enqueue_style('leaflet-pulse-icon-css', plugins_url('assets/css/L.Icon.Pulse.css',__FILE__));
    wp_enqueue_script('leaflet-pulse-icon-js', plugins_url('assets/js/L.Icon.Pulse.js',__FILE__), array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_leaflet_pulse_icon');


function enqueue_leaflet_geocoder() {
    wp_enqueue_style('geocoder-css', "https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css");
    wp_enqueue_script('geocoder-js', "https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js", array(), null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_leaflet_geocoder');

