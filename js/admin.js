jQuery(document).ready(function($) {
    var frame;
    $('#select_slider_media').on('click', function(e) {
        e.preventDefault();
        if (frame) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: 'Select Media for Slider',
            button: {
                text: 'Use these media'
            },
            library: {
                type: ['image', 'video']
            },
            multiple: true
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').toJSON();
            var attachmentIds = [];
            var preview = '';
            $.each(attachment, function(index, value) {
                attachmentIds.push(value.id);
                if (value.type === 'image') {
                    preview += '<img src="' + value.sizes.thumbnail.url + '" style="max-width:100px;max-height:100px;margin-right:10px;" />';
                } else if (value.type === 'video') {
                    preview += '<video src="' + value.url + '" style="max-width:100px;max-height:100px;margin-right:10px;" controls></video>';
                }
            });
            $('#slider_media').val(attachmentIds.join(','));
            $('#slider_media_preview').html(preview);
        });
        frame.open();
    });

    // Initialize color picker
    $('.color-field').wpColorPicker();
}); 