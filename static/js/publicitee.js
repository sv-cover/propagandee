/* Toggle committee, activity name, date-time and location fields.
 * Show location if selected activity has none.
 */
function toggle_activity_details(){
    var fields = [
        '#poster-request-committee',
        '#poster-request-activity-name',
        '#poster-request-date-time',
        '#poster-request-location'
    ];

    if ($('#poster-request-activity').val() === 'other')
        $(fields.join(', ')).parent().show();
    else {
        $(fields.join(', ')).parent().hide();
        var location = $('#poster-request-activity option:selected').data('location');
        if (location !== undefined && !location.trim())
            $('#poster-request-location').parent().show();
    }
}

// Toggle name and email fields
function toggle_applicant_details(){
    var fields = ['#poster-request-name', '#poster-request-email'];
    if ($('#poster-request-committee').val() === 'other')
        $(fields.join(', ')).parent().show();
    else
        $(fields.join(', ')).parent().hide();
}

// Enable activity selection field if it exists
if ($('#poster-request-activity').length) {
    toggle_activity_details();
    $('#poster-request-activity').change(function() {
        toggle_activity_details();
    });
    $('#poster-request-activity').parent().show();
}

// Enable committee selection field if it exists
if ($('#poster-request-committee')) {
    toggle_applicant_details();
    $('#poster-request-committee').change(function() {
        toggle_applicant_details();
    });

    if (!$('#poster-request-activity').length)
        $('#poster-request-committee').parent().show();
}

// Enable datetime picker
$('#poster-request-date-time').datetimepicker({
    locale: 'en-gb',
    format: 'YYYY-MM-DD h:mm',
    minDate: new Date(),
    defaultDate: new Date(new Date().getTime() + 24 * 3600 * 1000),
    stepping: 5,
    icons: {
        time: "fa fa-clock-o",
        date: "fa fa-calendar",
        up: "fa fa-arrow-up",
        down: "fa fa-arrow-down"
    }
});
