@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <h1>Book a Room</h1>
    <form action="{{ route('frontend.bookings.submit') }}" method="POST">
        @csrf
        <input type="hidden" name="room_id" value="{{ $room->id }}">
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="text" name="phone" id="phone" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="check_in">Check-In Date</label>
            <input type="text" name="check_in" id="check_in" class="form-control datepicker" required>
        </div>

        <div class="form-group">
            <label for="check_out">Check-Out Date</label>
            <input type="text" name="check_out" id="check_out" class="form-control datepicker" required>
        </div>

        <button type="submit" class="btn btn-primary">Submit Booking</button>
    </form>
</div>



@section('scripts')

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const roomId = {{ $room->id }};

    // Fetch unavailable dates from the server
    fetch(`{{ route('frontend.room.availability', ['roomId' => $room->id]) }}`)
        .then(response => response.json())
        .then(data => {
            const unavailableDates = data.unavailableDates;

            // Initialize Flatpickr
            flatpickr(".datepicker", {
                minDate: "today",
                dateFormat: "Y-m-d",
                disable: unavailableDates, // Disable unavailable dates
                onDayCreate: function(dObj, dStr, fp, dayElem) {
                    if (dayElem.classList.contains('flatpickr-disabled')) {
                        dayElem.style.backgroundColor = '#d3d3d3'; // Gray color for disabled days
                    } else {
                        dayElem.style.backgroundColor = '#007bff'; // Blue color for available days
                        dayElem.style.color = 'white';
                    }
                }
            });
        })
        .catch(error => console.error('Error fetching unavailable dates:', error));
});

</script>
@endsection
@endsection
