<?php

require_once 'admin/menu.php';
require_once 'templates/index.php';

//Add Leaflet library style
function OSM_enqueue_style() {
    wp_enqueue_style( 'osm-style', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.css', false );
    wp_enqueue_style( 'marker-style', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css', false );
    wp_enqueue_style( 'markercluster-style', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css', false );
}
add_action( 'wp_enqueue_scripts', 'OSM_enqueue_style' );

//Add Leaflet library script
function OSM_enqueue_script() {
    wp_enqueue_script( 'osm-map-js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.js', false );
    wp_enqueue_script( 'osm-cluster-map-js', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js', false );
}
add_action( 'wp_enqueue_scripts', 'OSM_enqueue_script' );


// Fonction pour traiter la requête AJAX
function process_contact_form() {
    // Vérifiez le nonce pour la sécurité
    check_ajax_referer('my-nonce', 'security');

    // Récupérez les données du formulaire
    $contactPermission = $_POST['contactPermission'];
    $wishContact = $_POST['wishContact'];
    $email = $_POST['email'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $phone = $_POST['phone'];
    $to = get_option('barycenter_email', get_option('admin_email'));
    $subject = 'Nouveau message du formulaire du barycentre';
    $message = "Nom : $firstName $lastName\nEmail : $email\nTéléphone : $phone\nPermission de contact : $contactPermission\nSouhaite être recontacté : $wishContact";
    $headers = array('Content-Type: text/plain; charset=UTF-8');


    // Traitez les données comme vous le souhaitez (par exemple, enregistrez-les dans une base de données, envoyez un e-mail, etc.)

    // Envoyez une réponse
    if (wp_mail($to, $subject, $message, $headers)) {
        wp_send_json_success(['message' => 'Formulaire soumis avec succès !']);
    } else {
        wp_send_json_error(['message' => 'Erreur lors de l\'envoi de l\'e-mail.']);
    }
}

// Ajoutez des actions AJAX pour les utilisateurs authentifiés et non authentifiés
add_action('wp_ajax_process_contact_form', 'process_contact_form'); // Si l'utilisateur est connecté
add_action('wp_ajax_nopriv_process_contact_form', 'process_contact_form'); // Si l'utilisateur n'est pas connecté

// Vérifie si un utilisateur a acheté un produit spécifique
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
