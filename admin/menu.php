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
            barycenter_render_text_field('barycenter_product_id', 'ID Product');
            barycenter_render_text_field('barycenter_redirect_url', 'Url de redirection sans abonnemnt');
            barycenter_render_input_field('barycenter_zoom', 'Zoom');
            barycenter_render_text_field('barycenter_longitude', 'Longitude');
            barycenter_render_text_field('barycenter_latitude', 'Latitude');
            barycenter_render_input_field('barycenter_limits', 'Limite de markers');
            barycenter_render_email_field('barycenter_email', 'E-mail');
            barycenter_render_checkbox_field('barycenter_enable_cluster', 'Activer les clusters');
            barycenter_render_colors_field('barycenter_color', 'Couleur du marker en avant');
            barycenter_render_checkbox_field('barycenter_enable_timer', 'Activer le timer pour la modale');
            if(get_option('barycenter_enable_timer') === 'on'){
                barycenter_render_text_field('barycenter_timer', 'Timer modale (en ms)');
            }
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function barycenter_render_colors_field($selected_color, $label) {
    $options = get_option('barycenter_color', array());

    $selected_color = isset($options['color']) ? $options['color'] : '';

    ?>
    <table class="form-table">
        <tr valign="top">
            <th for="color" scope="row"><?php echo $label; ?></th>
            <td>
                <select id="color" name="barycenter_color[color]">
                    <option value="blue" <?php selected($selected_color, 'blue'); ?>>Bleu</option>
                    <option value="violet" <?php selected($selected_color, 'violet'); ?>>Violet</option>
                    <option value="green" <?php selected($selected_color, 'green'); ?>>Vert</option>
                    <option value="gold" <?php selected($selected_color, 'gold'); ?>>Or</option>
                    <option value="black" <?php selected($selected_color, 'black'); ?>>Noir</option>
                    <option value="grey" <?php selected($selected_color, 'grey'); ?>>Gris</option>
                    <option value="red" <?php selected($selected_color, 'red'); ?>>Rouge</option>
                    <option value="orange" <?php selected($selected_color, 'orange'); ?>>Orange</option>
                    <option value="yellow" <?php selected($selected_color, 'yellow'); ?>>Jaune</option>
                </select>
            </td>
        </tr>
    </table>
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
        'latitude' => (float) get_option('barycenter_latitude'),
        'longitude' => (float) get_option('barycenter_longitude'),
        'zoom' => get_option('barycenter_zoom'),
        'product_id' => get_option('barycenter_product_id'),
        'redirect_url' => get_option( 'barycenter_redirect_url'),
        'hasPurchased' => has_user_purchased_product(get_current_user_id(), get_option('barycenter_product_id')),
        'timer' => get_option('barycenter_timer'),
        'enable_timer' => get_option('barycenter_enable_timer') === 'on' ? true : false,
        'enable_cluster' => get_option('barycenter_enable_cluster') === 'on' ? true : false,
        'limits' => get_option('barycenter_limits'),
        'option' =>  get_option('barycenter_color')
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
    register_setting('barycenter_options', 'barycenter_enable_cluster');
    register_setting('barycenter_options', 'barycenter_limits');
    register_setting('barycenter_options', 'barycenter_color');
    register_setting('barycenter_options', 'barycenter_redirect_url');

}
add_action('admin_init', 'barycenter_register_settings');


// Vérifie si un utilisateur a commandé un produit spécifique
function has_user_purchased_product($user_id, $product_id) {
    // Récupère toutes les commandes de l'utilisateur
    $customer_orders = get_posts([
        'numberposts' => -1,
        'meta_key'    => '_customer_user',
        'meta_value'  => $user_id,
        'post_type'   => 'shop_order',
        'post_status' => array('wc-completed') // Seules les commandes terminées
    ]);

    foreach ($customer_orders as $order) {
        $order_items = wc_get_order($order->ID)->get_items();

        foreach ($order_items as $item) {
            if ($item->get_product_id() == $product_id) {
                return true; // Produit trouvé dans les commandes de l'utilisateur
            }
        }
    }

    return false; // Produit non trouvé dans les commandes de l'utilisateur
}

function redirect_to_private_page() {
    if (is_page('app-barycentre')) { // Remplacez 'nom-de-votre-page' par le slug de votre page privée
        $user_id = get_current_user_id();
        $product_id = get_option('barycenter_product_id'); // Remplacez par l'ID de votre produit d'abonnement
        $redirect = get_option('barycenter_redirect_url');
        error_log("User ID: " . $user_id);
        error_log("Product ID: " . $product_id);
        error_log("Has Purchased: " . var_export(has_user_purchased_product($user_id, $product_id), true));

        if (!has_user_purchased_product($user_id, $product_id)) {
            wp_redirect($redirect ? $redirect : home_url()); // Redirige vers la page d'accueil si l'utilisateur n'a pas d'abonnement actif
            exit;
        }
    }
}
add_action('template_redirect', 'redirect_to_private_page');
