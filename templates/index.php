<?php

function barycenter_calculation_shortcode() {
    $user_id = get_current_user_id();
    $product_id = get_option('barycenter_product_id');
    ob_start();
    ?>
    <div id="app">
        <section class="grid">
            <div id="mapid">
                <input type="hidden" data-map-markers="" value="" name="map-geojson-data" />
            </div>
            <div class="card" id="result">
                <div class="card-body">
                    <h1><?php echo has_user_purchased_product($user_id, $product_id) ? '<span class="dashicons dashicons-backup open-modal pulse"></span>' : ''; ?> Calcul du barycentre</h1>
                    <hr>
                    <div class="step1">
                        <h2>Placer ses markers</h2>
                        <p><b>Commencez par Placer 2 points au minimum"</b><br>Puis placez sur la carte, les marqueurs qui devront être desservis (points de vente, entrepôts, clients, fournisseurs etc.)
                    </div>
                    <div class="hidden">
                        <h2>Saisir ses contraintes</h2>
                        <p>Les contraintes correspondent au volume de marchandises.<br>
                       Saisissez le volume acheminé.</p>
                        <table class="table">
                            <thead>
                                <th>Marker</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Tonnage estimé</th>
                            </thead>
                            <tbody class="coordinates"></tbody>
                        </table>
                        <br>
                        <button type="button" class="barycenter-button barycenter-button-primary" id="btn-calculation">Calculer le barycentre</button>
                        <button type="button" class="barycenter-button barycenter-button-secondary btn-reset">Recommencer</button>
                    </div>
                    <hr>
                </div>
                <a class="copy" target="_blank" href="https://www.mebdesign.fr">&copy; Mebdesign.fr</a>
            </div>
        </section>
        <section class="subscribers">
            <h2 style="text-align:center;">Vous n'avez pas encore d'historique de recherche</h2>
        <span class="close-modal">&times;</span>
        </section>
    </div>

    <!-- Modal -->
    <div class="custom-modal" id="contactModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Contactez-nous</h5>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="contactForm">
                    <div class="form-check">
                        <input type="checkbox" id="wishContact" name="wishContact">
                        <label for="wishContact">Je souhaite être contacté pour affiner la position du barycentre, j'ai d'autres contraintes</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="contactPermission" name="contactPermission">
                        <label for="contactPermission">Je souhaite rester en contact (Recevoir nos biens, nos articles etc.) ?</label>
                    </div>
                    <br>
                    <div class="form-group">
                        <label for="email">Votre email</label>
                        <input type="email" id="email" name="email" class="tonnage" placeholder="exemple@domaine.com" required>
                    </div>
                    <div class="form-group">
                        <label for="firstName">Prénom</label>
                        <input class="tonnage" type="text" id="firstName" name="firstName" placeholder="Prénom" required>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Nom</label>
                        <input class="tonnage" type="text" id="lastName" name="lastName" placeholder="Nom" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input class="tonnage" type="tel" id="phone" name="phone" placeholder="Téléphone" required>
                    </div>

                    <br>
                    <button class="barycenter-button barycenter-button-primary" type="submit">Envoyer</button>
                </form>
            </div>
        </div>
    </div>
    <span class="spinner"></span>


    <?php

    return ob_get_clean();
}
add_shortcode('barycenter_calculation', 'barycenter_calculation_shortcode');