(function($){

    var mediaUploader;
    $('#sa_image_upload').click(function(e){
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Select Announcement Image',
            button: { text: 'Select Image' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#sa_image').val(attachment.id);
            $('#sa_image_preview').attr('src', attachment.url);
        });
        mediaUploader.open();
    });

    $('#sa_image_remove').click(function(e){
        e.preventDefault();
        $('#sa_image').val('');
        $('#sa_image_preview').attr('src','');
    });

    $(document).on('click', '.sa-add-contact', function(){
        var container = $('#sa-contacts-container');
        var div = $('<div class="sa-contact">');
        div.html('<input type="text" name="sa_contacts_name[]" placeholder="Name" />' +
                ' <input type="text" name="sa_contacts_phone[]" placeholder="Phone / WhatsApp" />' +
                ' <button type="button" class="button sa-remove">Remove</button>');
        container.append(div);
    });

    $(document).on('click', '.sa-add-price', function(){
        var container = $('#sa-prices-container');
        var div = $('<div class="sa-price">');
        div.html('<input type="number" name="sa_prices_value[]" placeholder="Price" />' +
                ' <input type="text" name="sa_prices_name[]" placeholder="Category / Description" />' +
                ' <button type="button" class="button sa-remove">Remove</button>');
        container.append(div);
    });

    $(document).on('click', '.sa-add-speaker', function(){
        var container = $('#sa-speakers-container');
        var div = $('<div class="sa-speaker">');
        div.html('<input type="text" name="sa_speakers_name[]" placeholder="Name" />' +
                ' <input type="text" name="sa_speakers_prof[]" placeholder="Profession" />' +
                ' <input type="text" name="sa_speakers_org[]" placeholder="Organization" />' +
                ' <button type="button" class="button sa-remove">Remove</button>');
        container.append(div);
    });

    $(document).on('click', '.sa-add-organizer', function(){
        var container = $('#sa-organizers-container');
        var div = $('<div class="sa-organizer">');
        div.html('<input type="text" name="sa_organizers_name[]" placeholder="Organizer name" />' +
                ' <input type="text" name="sa_organizers_desc[]" placeholder="Description (optional)" />' +
                ' <button type="button" class="button sa-remove">Remove</button>');
        container.append(div);
    });

    $(document).on('click', '.sa-remove', function(){
        $(this).closest('.sa-contact, .sa-price, .sa-speaker, .sa-organizer').remove();
    });
})(jQuery);

