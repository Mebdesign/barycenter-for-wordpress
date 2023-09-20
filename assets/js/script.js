class BarycenterCalculator {
    constructor() {
        // Initialisation des propriétés de la classe
        this.map = null; // Référence à la carte Leaflet
        this.markers = []; // Liste des marqueurs ajoutés à la carte
        this.tonnes = []; // Liste des tonnes associées à chaque marqueur
        this.barycenterMarker = null; // Marqueur du barycentre
        // Définition de l'icône verte pour le marker
        let barycenterCalculator; // Déclaration globale
        this.markerClusterGroup = null; // Groupe de clustering pour les marqueurs
    }

    // Méthode pour initialiser la carte
    initializeMap() {
        // Configuration et ajout du fond de carte
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data &copy; <a href="http://www.osm.org">OpenStreetMap</a>'
        }).addTo(this.map);

        // Écouteur d'événement pour détecter les clics sur la carte
        this.map.on('click', this.onMapClick.bind(this));

        //initialiser le cluster
        if(barycenterParams.enable_cluster) {
            this.markerClusterGroup = L.markerClusterGroup();
            this.map.addLayer(this.markerClusterGroup);
        }


        // Ajustements CSS pour la mise en page responsive
        let gridElement = jQuery('.grid');
        let mapidElement = jQuery('#mapid');
        let resultElement = jQuery('#result');

        if (gridElement.width() < 980) {
            gridElement.css('height', 'auto');
            gridElement.css('display', 'block');
            mapidElement.css('display', 'block');
            mapidElement.css('width', '100%');
            resultElement.css('width', '100%');
            resultElement.css('height', 'auto');
        } else {
            mapidElement.css('display', '');
            resultElement.css('width', '');
        }

        // Ajustement de la taille de la carte après un zoom
        this.map.on('zoomend', () => {
            setTimeout(() => {
                this.map.invalidateSize();
            }, 200);
        });
    }

    // Méthode appelée lors d'un clic sur la carte
    onMapClick(e) {
        // Vérification de la confiance de l'événement pour éviter les faux clics
        if (e.originalEvent.isTrusted) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;


            // Vérification de la validité des coordonnées
            if (isNaN(lat) || isNaN(lng)) {
                console.error("Invalid coordinates:", lat, lng);
                return;
            }

            var limit = (typeof barycenterParams.limits !== 'undefined' && !isNaN(parseInt(barycenterParams.limits, 10))) ? parseInt(barycenterParams.limits, 10) : 3;

            if (!barycenterParams.hasPurchased  && barycenterCalculator.markers.length >= limit){
                alert("Vous avez atteint la limite de marqueurs pour la version gratuite. Veuillez vous abonner au produit pour ajouter des marqueurs à l'infini.");
                return;

            } else {

                // Création d'un nouveau marqueur et ajout à la carte
                const marker = this.createMarker(e.latlng);

                if(barycenterParams.enable_cluster) {
                    this.markerClusterGroup.addLayer(marker).openPopup();
                } else {
                    marker.addTo(this.map).openPopup();  // Ouvre la popup du marker
                }
                this.markers.push(marker);

                // Ajout d'une nouvelle ligne au tableau des coordonnées
                this.addTableRow(marker);

                const markerIndex = this.markers.indexOf(marker);
            }

            // Affichage du tableau si c'est le premier marqueur ajouté

                jQuery('.hidden').addClass('active').removeClass('hidden');
                jQuery('.step1').hide();
                jQuery('.active').show();
                jQuery('.card-body p').show();

        }
    }

    // Méthode pour créer un nouveau marqueur
    createMarker(latlng) {
        const marker = L.marker(latlng, {
            title: 'Resource Location',
            alt: 'Resource Location',
            riseOnHover: true,
            draggable: true
        });
        if(barycenterParams.enable_cluster) {
            // ajouter le marqueur au cluster plutôt qu'à la carte directement
            this.markerClusterGroup.addLayer(marker);
        }

        // Configuration de la popup du marqueur avec un bouton de suppression
        marker.bindPopup(`Marker: ${this.markers.length} <br><input type='button' value='Supprimer' class='marker-delete-button'/>`, {autoClose: false});

        // Écouteur d'événement pour détecter l'ouverture de la popup
        marker.on('popupopen', this.onPopupOpen.bind(this));

        return marker;
    }

    // Méthode pour ajouter une nouvelle ligne au tableau des coordonnées
    addTableRow(marker) {
        const lat = marker.getLatLng().lat.toFixed(6);
        const lng = marker.getLatLng().lng.toFixed(6);

        const markerIndex = this.markers.indexOf(marker);

        const row = `<tr data-marker-id="${markerIndex}">
            <td>${markerIndex}</td>
            <td>${lat}</td>
            <td>${lng}</td>
            <td><span class="dashicons dashicons-trash delete-marker"></span></td>
            <td><input id="tonnage" class="tonnage" type="number" value="" name="tonnage"></td>
        </tr>`;

        jQuery('.coordinates').append(row);
    }

    // Méthode appelée lors de l'ouverture de la popup d'un marqueur
    onPopupOpen() {
        const tempMarker = this.map._popup._source;

        // Détacher les événements précédents
        jQuery(document).off('click', '.marker-delete-button:visible');


        // Écouteur d'événement pour le bouton de suppression du marqueur
        jQuery(document).on('click', '.marker-delete-button:visible', () => {
            const markerIndex = this.markers.indexOf(tempMarker);

            // supprimer le marqueur du cluster
            if(barycenterParams.enable_cluster) {
                this.markerClusterGroup.removeLayer(tempMarker);
            }
            // Suppression du marqueur de la carte et du tableau des marqueurs
            this.map.removeLayer(tempMarker);
            this.markers.splice(markerIndex, 1);

            // Suppression de la ligne correspondante dans le tableau des coordonnées
            jQuery('.coordinates tr').eq(markerIndex).remove();

            // Mise à jour des marqueurs restants et du tableau
            this.updateMarkersAndTable();
        });
    }

    // Méthode pour mettre à jour les marqueurs et le tableau après la suppression d'un marqueur
    updateMarkersAndTable() {
        this.markers.forEach((marker, index) => {
            // Mise à jour du contenu de la popup pour chaque marker
            marker.getPopup().setContent(`Marker: ${index } <br><input type='button' value='Supprimer' class='marker-delete-button'/>`);

            // Mise à jour de l'index dans le tableau .coordinates
            jQuery('.coordinates tr').eq(index).children('td:first').text(index );
        });
    }


    // Méthode pour réinitialiser le barycentre
    resetBarycenter() {
        if (this.barycenterMarker) {
            this.map.removeLayer(this.barycenterMarker);
            this.barycenterMarker = null;
        }

    }


    // Méthode pour récupérer les tonnes saisies et calculer le barycentre
    getInputTonnage() {
        const tonnesInputs = jQuery('.coordinates input.tonnage');
        this.tonnes = [];
        tonnesInputs.each((index, input) => {
            const tonnageValue = parseFloat(input.value);
            if (isNaN(tonnageValue)) {
                alert('Veuillez entrer un nombre valide pour le tonnage.');
                return;
            }
            this.tonnes.push(tonnageValue);
        });

        // Récupérez les markers ici
        let markersData = this.markers.map((marker, index) => {
            return { lat: marker.getLatLng().lat, lng: marker.getLatLng().lng, tonnage: this.tonnes[index]  };
        });

        // Avant de commencer la requête AJAX
        jQuery(".spinner").addClass("is-active");

        // Requête AJAX pour calculer le barycentre
        jQuery.ajax({
            url: barycenterParams.ajax_url,
            type: 'POST',
            data: {
                action: 'calculate_barycenter',
                markers: markersData,
                tonnages: this.tonnes,
                security: barycenterParams.security
            },
            success: (response) => {
                // Dans le callback de succès de votre requête AJAX
                jQuery(".spinner").removeClass("is-active");
                if (response.success) {
                    // Utilisez les données renvoyées pour afficher le barycentre
                    const barycenter = response.data.barycenter;
                    // Utilisez barycenter.lat et barycenter.lng pour afficher le barycentre sur la carte
                    this.calculateAndDisplayBarycenter();
                    if( barycenterParams.hasPurchased ){
                        updateBarycenterHistory()
                    }
                } else {
                    alert('Erreur lors du calcul du barycentre.');
                }
            },
            error: function() {
                alert('Erreur lors du calcul du barycentre.');
                // Et aussi dans le callback d'erreur de votre requête AJAX
                jQuery(".spinner").removeClass("is-active");

            }
        });
    }


    // Méthode pour calculer le barycentre
    calculateBarycenter() {
        this.resetBarycenter();

        if (this.markers.length === 0 || this.tonnes.length === 0) {
            return;
        }

        const totals = this.markers.reduce(
            (acc, marker, index) => {
                const lat = marker.getLatLng().lat;
                const lng = marker.getLatLng().lng;
                const tonnage = this.tonnes[index];

                acc.totalTxi += lng * tonnage;
                acc.totalTyi += lat * tonnage;
                acc.totalTon += tonnage;

                return acc;
            },
            { totalTxi: 0, totalTyi: 0, totalTon: 0 }
        );

        const lng = (totals.totalTxi / totals.totalTon).toFixed(6);
        const lat = (totals.totalTyi / totals.totalTon).toFixed(6);

        const pulseIcon = new L.icon.pulse();
        this.barycenterMarker = L.marker([lat, lng], {icon: pulseIcon}).addTo(this.map);

        this.barycenterMarker.addTo(this.map);

        return { lat, lng };
    }

    // Méthode pour calculer et afficher le barycentre
    calculateAndDisplayBarycenter() {
        const { lat, lng } = this.calculateBarycenter();

        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
        .then(response => response.json())
        .then(data => {
            this.barycenterMarker.bindPopup(data.display_name).openPopup();

            const commentResult = `
                <p style="margin:10px;">
                    Le barycentre est situé à la latitude <b>${this.barycenterMarker._latlng.lat.toFixed(6)}</b>
                    et à la longitude <b>${this.barycenterMarker._latlng.lng.toFixed(6)}</b>
                    et correspond à l'adresse : ${data.display_name}<br> <b>Nous pouvons vous aider à affiner votre recherche.</b>
                </p> `;

            jQuery('.active').nextAll().remove();
            jQuery('.active').after(commentResult);


        });

        if (!barycenterParams.hasPurchased && barycenterParams.enable_timer) {
            setTimeout(() => {
                jQuery('#contactModal').css('display', 'flex');
            }, barycenterParams.timer || 5000);
        }
    }


    // Méthode pour réinitialiser l'application
    reset() {
        location.reload();
    }

    // Fonction pour mettre en évidence un marker en changeant son icône en vert
    highlightMarker(marker) {

        // Réinitialiser tous les markers à l'icône bleue
        this.resetAllMarkersToDefault();

        // Changez l'icône du marker pour le mettre en évidence
        let color = barycenterParams.option.color || 'red'
        const greenIcon = new L.Icon({
            iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${color}.png`,
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        marker.setIcon(greenIcon);
    }

    resetAllMarkersToDefault() {
        this.markers.forEach(marker => {
            marker.setIcon(L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png', // Remplacez par le chemin de votre icône bleue
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34]
            }));
        });
    }


} // Fin BarycenterCalculator class


// Fonction pour télécharger le CSV

function downloadCSV(csv, filename) {
    let csvFile;
    let downloadLink;

    // Créer un nouveau Blob avec le contenu CSV
    csvFile = new Blob([csv], {type: "text/csv"});

    // Créer un lien de téléchargement pour le CSV
    downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.style.display = "none";

    // Ajouter le lien au corps du document et cliquer dessus pour démarrer le téléchargement
    document.body.appendChild(downloadLink);
    downloadLink.click();
}

function updateBarycenterHistory() {
    // Avant de commencer la requête AJAX
    jQuery(".spinner").addClass("is-active");
    jQuery.ajax({
        url: barycenterParams.ajax_url,
        type: 'POST',
        data: {
            action: 'get_barycenter_history',
            security: barycenterParams.security
        },
        success: (response) => {
            jQuery(".spinner").removeClass("is-active");
            if (response.success) {
                // Ici, construisez le tableau HTML à partir des données renvoyées
                // et mettez à jour le contenu de la div.
                let historyHTML = buildHistoryTable(response.data);
                jQuery('.subscribers').html(historyHTML);
                // Si vous souhaitez colorer les lignes après avoir ajouté l'historique à la table
                colorAlternateRows();

            } else {
                alert(response.data);
            }
        },
        error: function() {
            jQuery(".spinner").removeClass("is-active");
            alert('Erreur lors de la récupération de l\'historique.');
        }
    });
}

function buildHistoryTable(history) {
    if (!history || history.length === 0) {
        return "Vous n'avez pas encore d'historique de recherche.";
    }

    let output = '<table class="barycenter-history-table">';
    output += '<h2>Mon historique de recherche</h2><thead><tr><th>Date</th><th>Latitude</th><th>Longitude</th><th>Tonnage</th><th>Barycenter Latitude</th><th>Barycenter Longitude</th><th>Action</th></tr></thead><tbody>';

    const exportButton = `<button class="barycenter-button barycenter-button-secondary" id="exportToCSV">Exporter en CSV</button>`;

    history.forEach(entry => {
        let markers = entry.markers; // Supposons que les markers sont déjà désérialisés
        let firstRow = true;

        markers.forEach(marker => {
            if (firstRow) {
                output += `<tr data-entry-id='${entry.id}'>
                    <td rowspan="${markers.length}">${entry.timestamp}</td>
                    <td>${marker.lat}</td>
                    <td>${marker.lng}</td>
                    <td>${marker.tonnage}</td>
                    <td rowspan="${markers.length}">${entry.barycenter_latitude}</td>
                    <td rowspan="${markers.length}">${entry.barycenter_longitude}</td>
                    <td rowspan="${markers.length}"><button data-entry-id='${entry.id}' class='delete-history-entry barycenter-button barycenter-button-danger'>Supprimer</button></td>
                </tr>`;
                firstRow = false;
            } else {
                output += `<tr data-entry-id='${entry.id}'>
                    <td>${marker.lat}</td>
                    <td>${marker.lng}</td>
                    <td>${marker.tonnage}</td>
                </tr>`;
            }
        });
    });

    output += `</tbody>${barycenterParams.hasPurchased ? exportButton : "Veuillez vous abonner"}<span class="close-modal">&times;</span></table>`;

    return output;
}

function colorAlternateRows() {
    let currentEntryId = null;
    let currentColor = 'white';  // couleur initiale

    jQuery('.barycenter-history-table tbody tr').each(function() {
        const rowEntryId = jQuery(this).data('entry-id');

        if (currentEntryId !== rowEntryId) {
            currentEntryId = rowEntryId;
            currentColor = (currentColor === 'white') ? '#f2f2f2' : 'white';  // alterner la couleur
        }

        jQuery(this).css('background-color', currentColor);
    });
}


// Événement de focus sur une ligne du tableau .coordinates
jQuery(document).on('click', '.coordinates tr', function() {

    // Récupération de l'index de la ligne sur laquelle le focus est effectué
    const rowIndex = jQuery(this).index();

    // Réinitialisation de tous les markers à l'icône bleue
    barycenterCalculator.resetAllMarkersToDefault();

    // Récupération du marker correspondant à partir de l'array this.markerL'idée c'est de trouver un prix raisos
    const markerToHighlight = barycenterCalculator.markers[rowIndex];

    // Appel de la fonction pour mettre en évidence le marker
    barycenterCalculator.highlightMarker(markerToHighlight);
});

// Événement pour détecter un clic en dehors du tableau
jQuery(document).on('click', function(event) {
    if (!jQuery(event.target).closest('.coordinates').length) {
        // Si le clic n'était pas à l'intérieur du tableau .coordinates
        barycenterCalculator.resetAllMarkersToDefault();
    }
});


// Initialisation de l'application lorsque le document est prêt
jQuery(document).ready(function () {

    barycenterCalculator = new BarycenterCalculator();

        barycenterCalculator.map = L.map('mapid').setView([
            barycenterParams.latitude || 53,
            barycenterParams.longitude || -3
        ], barycenterParams.zoom || 6);


    barycenterCalculator.initializeMap();

    jQuery(document).on('click', '.delete-marker', function() {
        const markerIndex = jQuery(this).closest('tr').data('marker-id');
        const tempMarker = this.markers[markerIndex];

        // Supprimer le marqueur du cluster
        if(barycenterParams.enable_cluster) {
            this.markerClusterGroup.removeLayer(tempMarker);
        }

        // Suppression du marqueur de la carte et du tableau des marqueurs
        this.map.removeLayer(tempMarker);
        this.markers.splice(markerIndex, 1);

        // Suppression de la ligne correspondante dans le tableau des coordonnées
        jQuery('.coordinates tr').eq(markerIndex + 1).remove(); // +1 car l'index 0 est l'en-tête du tableau

        // Mise à jour des marqueurs restants et du tableau
        this.updateMarkersAndTable();
    });


    jQuery(document).on('click', '#exportToCSV', exportUserHistoryToCsv);
    jQuery(document).on('click', '#btn-calculation', barycenterCalculator.getInputTonnage.bind(barycenterCalculator));
    jQuery(document).on('click', '.btn-reset', barycenterCalculator.reset);
    jQuery(document).on('click', '.btn-backHome', () => {
        const home = window.location.origin;
        location.replace(home);
    });

    var geocoder = L.Control.geocoder({
        geocoder: L.Control.Geocoder.nominatim(), // Utilisez Nominatim comme service de géocodage
        defaultMarkGeocode: false
    })
    .on('markgeocode', function(e) {
        var bbox = e.geocode.bbox;
        var poly = L.polygon([
             bbox.getSouthEast(),
             bbox.getNorthEast(),
             bbox.getNorthWest(),
             bbox.getSouthWest()
        ]);
        barycenterCalculator.map.fitBounds(poly.getBounds());

        // Ajoutez un marqueur à l'emplacement recherché
        const marker = barycenterCalculator.createMarker(e.geocode.center);
        marker.addTo(barycenterCalculator.map).openPopup();
        barycenterCalculator.markers.push(marker);
        barycenterCalculator.addTableRow(marker);
    })
    .addTo(barycenterCalculator.map);

    // load history when document is ready
    if( barycenterParams.hasPurchased ){
        updateBarycenterHistory()
    }

});


//delete history
function setupDeleteHistoryEvent() {
    jQuery(document).on('click', '.delete-history-entry', function() {
        const entryId = jQuery(this).data('entry-id');
        jQuery(".spinner").addClass("is-active");
        // Requête AJAX pour supprimer l'entrée d'historique.
        jQuery.ajax({
            url: barycenterParams.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_barycenter_history_entry',
                entry_id: entryId,
                security: barycenterParams.security
            },
            success: function(response) {
                jQuery(".spinner").removeClass("is-active");
                if (response.success) {
                    // Supprimez la ligne du tableau en utilisant la variable temporaire
                    jQuery('tr[data-entry-id="' + entryId + '"]').remove();

                } else {
                    alert('Erreur lors de la suppression.');
                }
            }.bind(this),
            error: function() {
                jQuery(".spinner").removeClass("is-active");
                alert('Erreur lors de la suppression.');
            }
        });
    });

}


function exportUserHistoryToCsv() {
    jQuery(".spinner").addClass("is-active");
    jQuery.ajax({
        url: barycenterParams.ajax_url,
        type: 'POST',
        data: {
            action: 'export_user_history',
            user_id: barycenterParams.userId,
            security: barycenterParams.security
        },
        success: function(response) {
            jQuery(".spinner").removeClass("is-active");
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.success) {
                downloadCSV(parsedResponse.data, 'user_history.csv');
            } else {
                alert('Erreur lors de l\'exportation des données.');
            }
        },
        error: function() {
            jQuery(".spinner").removeClass("is-active");
            alert('Erreur lors de la demande d\'exportation.');
        }
    });
}


// Lancer la fonction dès que le DOM est prêt
jQuery(document).ready(function($) {
    setupDeleteHistoryEvent();
});

// Écouteurs d'événements pour la modale et le formulaire de contact
jQuery(document).on('click', '.close-modal, #contactModal', function(event) {
    // Si l'utilisateur a cliqué sur .close-modal ou en dehors de .modal-content
    if (jQuery(event.target).hasClass('close-modal') || jQuery(event.target).closest('.modal-content').length === 0) {
        jQuery('#contactModal').css('display', 'none');
        jQuery('.subscribers').fadeOut();
    }
});
jQuery(document).on('click', '.open-modal', function(event) {
    // Si l'utilisateur a cliqué sur .open-modal
        jQuery('.subscribers').fadeIn();

});

jQuery(document).on('submit', '#contactForm', function(e) {
    e.preventDefault();

    let formData = jQuery(this).serialize();
    formData += '&action=process_contact_form';
    formData += '&security=' + barycenterParams.security;
    jQuery(".spinner").addClass("is-active");

    jQuery.ajax({
        type: 'POST',
        url: barycenterParams.ajax_url,
        data: formData,
        dataType: 'json',
        success: function(response) {
            jQuery(".spinner").removeClass("is-active");
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Erreur : ' + response.data.message);
            }
        },
        error: function() {
            jQuery(".spinner").removeClass("is-active");
            alert('Erreur lors de l\'envoi du formulaire. Veuillez réessayer.');
        }
    });

    const firstName = jQuery('#firstName').val();
    const lastName = jQuery('#lastName').val();
    const phone = jQuery('#phone').val();
    const email = jQuery('#email').val();

    if (!firstName || !lastName || !phone || !email) {
        alert("Veuillez remplir tous les champs.");
        return;
    }

    jQuery('#contactModal').css('display', 'none');
});


