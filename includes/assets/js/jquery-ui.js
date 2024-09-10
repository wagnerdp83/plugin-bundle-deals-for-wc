jQuery(document).ready(function($) {
    if ($.fn.datepicker) {
        $('.date-picker').datepicker({
            dateFormat: 'yy-mm-dd', // Format for the date picker
            changeMonth: true,     // Allows changing month
            changeYear: true,      // Allows changing year
            yearRange: "1900:2100" // Range of years to be displayed
        });
    } else {
        console.error("jQuery UI is not loaded. The datepicker function is not available.");
    }
});