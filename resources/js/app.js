import './bootstrap.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

document.addEventListener('DOMContentLoaded', function() {
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        minDate: "today" // Set the minimum date to today
    });
});
