jQuery(document).ready(function($) {
    var frame;
    $('#select_slider_images').on('click', function(e) {
        e.preventDefault();
        if (frame) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: 'Select Images for Slider',
            button: {
                text: 'Use these images'
            },
            multiple: true
        });
        frame.on('select', function() {
            var attachment = frame.state().get('selection').toJSON();
            var attachmentIds = [];
            var preview = '';
            $.each(attachment, function(index, value) {
                attachmentIds.push(value.id);
                preview += '<img src="' + value.sizes.thumbnail.url + '" style="max-width:100px;max-height:100px;margin-right:10px;" />';
            });
            $('#slider_images').val(attachmentIds.join(','));
            $('#slider_image_preview').html(preview);
        });
        frame.open();
    });

    // Initialize color picker
    $('.color-field').wpColorPicker();
}); 