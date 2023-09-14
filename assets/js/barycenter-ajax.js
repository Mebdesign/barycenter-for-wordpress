function saveSearch(markers, barycenter) {
    jQuery.ajax({
        url: barycenterAjax.ajaxurl,
        type: 'POST',
        data: {
            action: 'save_barycenter_search',
            markers: markers,
            barycenter: barycenter,
            nonce: barycenterAjax.nonce
        },
        success: function(response) {
            if(response.success) {
                alert('Recherche enregistrée avec succès.');
            } else {
                alert('Erreur lors de l\'enregistrement.');
            }
        }
    });
}
