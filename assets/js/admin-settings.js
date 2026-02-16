(function($) {
    'use strict';

    // Add row
    $(document).on('click', '.abox-repeater-add', function() {
        var fieldId = $(this).data('field-id');
        var tbody = $(this).closest('table').find('tbody');
        var index = tbody.find('tr').length;

        var row = '<tr class="abox-repeater-row">' +
            '<td><input type="text" name="' + fieldId + '[' + index + '][slug]" value="" class="abox-repeater-slug" style="width:120px;" required></td>' +
            '<td><input type="text" name="' + fieldId + '[' + index + '][label]" value="" class="abox-repeater-label" style="width:200px;" required></td>' +
            '<td><input type="color" name="' + fieldId + '[' + index + '][bg_color]" value="#dd3333" class="abox-repeater-bg-color"></td>' +
            '<td><input type="color" name="' + fieldId + '[' + index + '][text_color]" value="#ffffff" class="abox-repeater-text-color"></td>' +
            '<td><mark class="order-status abox-repeater-preview" style="background-color:#dd3333;color:#ffffff;"><span></span></mark></td>' +
            '<td><button type="button" class="button abox-repeater-remove" title="Remove"><span class="dashicons dashicons-trash"></span></button></td>' +
            '</tr>';

        tbody.append(row);
    });

    // Remove row
    $(document).on('click', '.abox-repeater-remove', function() {
        var tbody = $(this).closest('tbody');
        $(this).closest('tr').remove();
        // Re-index
        tbody.find('tr').each(function(i) {
            $(this).find('input').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                }
            });
        });
    });

    // Live preview update
    $(document).on('input change', '.abox-repeater-row input', function() {
        var row = $(this).closest('.abox-repeater-row');
        var label = row.find('.abox-repeater-label').val();
        var bgColor = row.find('.abox-repeater-bg-color').val();
        var textColor = row.find('.abox-repeater-text-color').val();
        var preview = row.find('.abox-repeater-preview');

        preview.css({ 'background-color': bgColor, 'color': textColor });
        preview.find('span').text(label);
    });

})(jQuery);
