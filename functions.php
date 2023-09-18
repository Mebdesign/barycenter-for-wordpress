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


function calculate_barycenter() {
    error_log(print_r($_POST, true));
    // Vérifiez le nonce pour la sécurité
    check_ajax_referer('my-nonce', 'security');

    // Récupérez les données du formulaire
    $markers = isset($_POST['markers']) ? $_POST['markers'] : array();

    // Vérifiez si des marqueurs ont été fournis
    if (empty($markers)) {
        wp_send_json_error('Aucun marqueur fourni.');
    }

    // Initialisation des sommes pour les latitudes et les longitudes
    $sum_lat = 0;
    $sum_lng = 0;

    // Calculez la somme des latitudes et des longitudes
    foreach ($markers as $marker) {
        $sum_lat += $marker['lat'];
        $sum_lng += $marker['lng'];
    }

    // Calculez les moyennes pour obtenir le barycentre
    $barycenter_lat = $sum_lat / count($markers);
    $barycenter_lng = $sum_lng / count($markers);

    // Enregistrez le barycentre dans la base de données
    $user_id = get_current_user_id(); // Assurez-vous que l'utilisateur est connecté
    if ($user_id) {
        add_barycenter_history($user_id, $markers, $barycenter_lat, $barycenter_lng);
    }

    // Renvoyez le barycentre
    wp_send_json_success(['barycenter' => ['lat' => $barycenter]]);
}

// Ajoutez des actions AJAX pour les utilisateurs authentifiés et non authentifiés
add_action('wp_ajax_process_contact_form', 'process_contact_form'); // Si l'utilisateur est connecté
add_action('wp_ajax_nopriv_process_contact_form', 'process_contact_form'); // Si l'utilisateur n'est pas connecté
add_action('wp_ajax_calculate_barycenter', 'calculate_barycenter');
add_action('wp_ajax_nopriv_calculate_barycenter', 'calculate_barycenter');


// Cette fonction crée une nouvelle table dans la base de données lors de l'activation du plugin.
function create_barycenter_history_table() {
    global $wpdb; // Accéder à la variable globale $wpdb pour interagir avec la base de données.
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'barycenter_history'; // Nom de la table avec le préfixe WordPress.

    // SQL pour créer la table.
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT, -- ID unique pour chaque entrée.
        user_id mediumint(9) NOT NULL, -- ID de l'utilisateur.
        markers text NOT NULL, -- Markers sous forme de texte (sérialisé).
        barycenter_lat float(10, 6) NOT NULL, -- Latitude du barycentre.
        barycenter_lng float(10, 6) NOT NULL, -- Longitude du barycentre.
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL, -- Date et heure de la recherche.
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // Inclure le fichier upgrade.php pour utiliser la fonction dbDelta.
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql); // Exécuter la requête SQL.
}

// Exécuter la fonction lors de l'activation du plugin.
register_activation_hook(__FILE__, 'create_barycenter_history_table');

// Cette fonction ajoute une nouvelle entrée à la table d'historique.
function add_barycenter_history($user_id, $markers, $barycenter_lat, $barycenter_lng) {
    global $wpdb; // Accéder à la variable globale $wpdb.
    $table_name = $wpdb->prefix . 'barycenter_history'; // Nom de la table avec le préfixe WordPress.

    // Insérer les données dans la table.
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'markers' => maybe_serialize($markers), // Sérialiser les markers pour les stocker sous forme de texte.
            'barycenter_latitude' => $barycenter_lat,
            'barycenter_longitude' => $barycenter_lng,
            'timestamp' => current_time('mysql') // Utiliser le temps actuel pour le timestamp.
        )
    );
}

// Cette fonction récupère l'historique des recherches d'un utilisateur.
function get_barycenter_history($user_id) {
    global $wpdb; // Accéder à la variable globale $wpdb.
    $table_name = $wpdb->prefix . 'barycenter_history'; // Nom de la table avec le préfixe WordPress.

    // Exécuter la requête pour obtenir toutes les entrées de l'utilisateur triées par date.
    $results = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY timestamp DESC", $user_id)
    );

    return $results; // Retourner les résultats.
}

function ajax_get_barycenter_history() {
    // Vérifiez si l'utilisateur est connecté.
    if (!is_user_logged_in()) {
        wp_send_json_error("Vous devez être connecté pour voir votre historique.");
    }

    $user_id = get_current_user_id();
    $history = get_barycenter_history($user_id);

    if (empty($history)) {
        wp_send_json_error("Vous n'avez pas encore d'historique de recherche.");
    }

    // Désérialisez les markers pour chaque entrée de l'historique.
    foreach ($history as $entry) {
        $entry->markers = maybe_unserialize($entry->markers);
    }

    wp_send_json_success($history);
}
add_action('wp_ajax_get_barycenter_history', 'ajax_get_barycenter_history');



function delete_barycenter_history_entry() {
    // Vérifiez la sécurité.
    check_ajax_referer('my-nonce', 'security');

    $entry_id = isset($_POST['entry_id']) ? intval($_POST['entry_id']) : 0;

    if (!$entry_id) {
        wp_send_json_error('ID d\'entrée invalide.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'barycenter_history';
    $result = $wpdb->delete($table_name, array('id' => $entry_id));

    if ($result) {
        wp_send_json_success('Entrée supprimée avec succès.');
    } else {
        wp_send_json_error('Erreur lors de la suppression de l\'entrée.');
    }
}
add_action('wp_ajax_delete_barycenter_history_entry', 'delete_barycenter_history_entry');
