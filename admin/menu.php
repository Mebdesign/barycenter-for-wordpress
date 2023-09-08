<?php
// Fichier : admin/menu.php

function register_barycenter_calculation_menu_page() {
    add_menu_page(
        'Calcul du barycentre', // Titre de la page du menu
        'Calcul du barycentre', // Texte du menu
        'manage_options', // Capacité requise pour accéder à la page
        'barycenter-calculation', // Slug de la page
        'barycenter_settings_page', // Fonction de rappel pour afficher la page de paramètres
        'dashicons-location', // Icône du menu (facultatif)
        30 // Position du menu dans la barre de navigation
    );
}
add_action('admin_menu', 'register_barycenter_calculation_menu_page');

function barycenter_settings_page() {
    ?>
    <div class="wrap">
        <h1>Barycenter Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('barycenter_options');
            do_settings_sections('barycenter_options');
            barycenter_render_input_field('barycenter_limits', 'Limite de markers');
            barycenter_render_input_field('barycenter_latitude', 'Latitude');
            barycenter_render_input_field('barycenter_longitude', 'Longitude');
            barycenter_render_input_field('barycenter_zoom', 'Zoom');
            barycenter_render_email_field('barycenter_email', 'E-mail');
            barycenter_render_text_field('barycenter_product_id', 'ID Product');
            barycenter_render_checkbox_field('barycenter_enable_timer', 'Activer le timer pour le modal');
            if(get_option('barycenter_enable_timer') === 'on'){
                barycenter_render_text_field('barycenter_timer', 'Timer modale (en ms)');
            }
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function barycenter_render_checkbox_field($field_name, $label) {
    $field_value = esc_attr(get_option($field_name));
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo $label; ?></th>
            <td><input type="checkbox" name="<?php echo $field_name; ?>" <?php checked($field_value, 'on'); ?> /></td>
        </tr>
    </table>
    <?php
}


function barycenter_render_text_field($field_name, $label) {
    $field_value = esc_attr(get_option($field_name));
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo $label; ?></th>
            <td><input type="text" name="<?php echo $field_name; ?>" value="<?php echo $field_value; ?>" /></td>
        </tr>
    </table>
    <?php
}

function barycenter_render_input_field($field_name, $label) {
    $field_value = esc_attr(get_option($field_name));
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo $label; ?></th>
            <td><input type="number" name="<?php echo $field_name; ?>" value="<?php echo $field_value; ?>" /></td>
        </tr>
    </table>
    <?php
}

function barycenter_render_email_field($field_name, $label) {
    $field_value = esc_attr(get_option($field_name, get_option('admin_email'))); // Utilisez l'e-mail de l'admin par défaut
    ?>
    <table class="form-table">
        <tr valign="top">
            <th scope="row"><?php echo $label; ?></th>
            <td><input type="email" name="<?php echo $field_name; ?>" value="<?php echo $field_value; ?>" /></td>
        </tr>
    </table>
    <?php
}


function barycenter_enqueue_scripts() {
    wp_enqueue_script('barycenter-js', plugins_url('../assets/js/script.js', __FILE__), array('jquery', 'osm-map-js', 'osm-cluster-map-js'), '1.0', false);

    $barycenter_params = array(
        'latitude' => get_option('barycenter_latitude'),
        'longitude' => get_option('barycenter_longitude'),
        'zoom' => get_option('barycenter_zoom'),
        'product_id' => get_option('barycenter_product_id'),
        'hasPurchased' => has_user_purchased_product(get_current_user_id(), get_option('barycenter_product_id')),
        'timer' => get_option('barycenter_timer_modale'),
        'enable_timer' => get_option('barycenter_enable_timer') === 'on' ? true : false,
        'limits' => get_option('barycenter_limits'),
    );

    wp_localize_script('barycenter-js', 'barycenterParams', $barycenter_params);
}
add_action('wp_enqueue_scripts', 'barycenter_enqueue_scripts');


function barycenter_register_settings() {
    register_setting('barycenter_options', 'barycenter_latitude');
    register_setting('barycenter_options', 'barycenter_longitude');
    register_setting('barycenter_options', 'barycenter_zoom');
    register_setting('barycenter_options', 'barycenter_email');
    register_setting('barycenter_options', 'barycenter_product_id');
    register_setting('barycenter_options', 'barycenter_timer');
    register_setting('barycenter_options', 'barycenter_enable_timer');
    register_setting('barycenter_options', 'barycenter_limits');

}
add_action('admin_init', 'barycenter_register_settings');
