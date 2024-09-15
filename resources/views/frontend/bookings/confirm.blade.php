@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <h1>Confirm Your Booking</h1>

    <div class="card">
        <div class="card-body">
            <h4>Booking Details</h4>
            <p><strong>Room:</strong> {{ $booking->room->name }}</p>
            <p><strong>Name:</strong> {{ $booking->name }}</p>
            <p><strong>Email:</strong> {{ $booking->email }}</p>
            <p><strong>Phone:</strong> {{ $booking->phone }}</p>
            <p><strong>Check-In:</strong> {{ $booking->check_in }}</p>
            <p><strong>Check-Out:</strong> {{ $booking->check_out }}</p>
        </div>
    </div>

    <form id="payment-form" action="{{ route('frontend.bookings.selectPayment', $booking->id) }}" method="POST">
        @csrf
        <div class="form-group mt-3">
            <label for="payment_method">Select Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-control" required>
                <option value="">Choose...</option>
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
                <option value="cash">Cash</option>
            </select>
        </div>

        <!-- Stripe payment fields -->
        <div id="stripe-fields" class="form-group mt-3" style="display: none;">
            <label for="card-element">Credit or Debit Card</label>
            <div id="card-element"></div>
            <div id="card-errors" role="alert"></div>
        </div>

        <!-- PayPal button -->
        <div id="paypal-button-container" class="form-group mt-3" style="display: none;">
            <!-- PayPal Button will be rendered here -->
        </div>

        <button id="submit-button" type="submit" class="btn btn-success mt-3">Confirm and Pay Later</button>
    </form>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script src="https://www.paypal.com/sdk/js?client-id={{ config('services.paypal.client_id') }}"></script>
<script>
    document.getElementById('payment_method').addEventListener('change', function() {
        const stripeFields = document.getElementById('stripe-fields');
        const paypalButtonContainer = document.getElementById('paypal-button-container');
        const submitButton = document.getElementById('submit-button');

        if (this.value === 'stripe') {
            stripeFields.style.display = 'block';
            paypalButtonContainer.style.display = 'none';
            submitButton.textContent = 'Confirm and Pay'; // Stripe button text
        } else if (this.value === 'paypal') {
            stripeFields.style.display = 'none';
            paypalButtonContainer.style.display = 'block';
            renderPayPalButton();
            submitButton.style.display = 'none'; // Hide the submit button for PayPal
        } else if (this.value === 'cash') {
            stripeFields.style.display = 'none';
            paypalButtonContainer.style.display = 'none';
            submitButton.textContent = 'Confirm and Pay Later'; // Cash button text
            submitButton.style.display = 'block'; // Ensure the submit button is visible
        } else {
            stripeFields.style.display = 'none';
            paypalButtonContainer.style.display = 'none';
            submitButton.textContent = 'Confirm Booking'; // Default button text
        }
    });

    function renderPayPalButton() {
        paypal.Buttons({
            createOrder: function(data, actions) {
                return actions.order.create({
                    purchase_units: [{
                        amount: {
                            value: '10.00' // Amount in USD
                        },
                        description: 'Room Booking'
                    }],
                    application_context: {
                        return_url: '{{ route('frontend.bookings.paypal.callback') }}',
                        cancel_url: '{{ route('frontend.bookings.confirm', ['bookingId' => $booking->id]) }}'
                    }
                });
            },
            onApprove: function(data, actions) {
                return actions.order.capture().then(function(details) {
                    document.getElementById('payment-form').submit(); // Submit the form after payment
                });
            },
            onError: function(err) {
                console.error('PayPal Checkout Error:', err);
            }
        }).render('#paypal-button-container');
    }

    var stripe = Stripe('{{ config('services.stripe.key') }}');
    var elements = stripe.elements();
    var cardElement = elements.create('card');
    cardElement.mount('#card-element');

    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        const paymentMethod = document.getElementById('payment_method').value;
        
        if (paymentMethod === 'stripe') {
            event.preventDefault(); // Prevent default form submission for Stripe
            stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            }).then(function(result) {
                if (result.error) {
                    var errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                } else {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'payment_method_id');
                    hiddenInput.setAttribute('value', result.paymentMethod.id);
                    form.appendChild(hiddenInput);
                    form.submit(); // Submit the form after adding the hidden input
                }
            });
        } else if (paymentMethod === 'cash') {
            // No need to prevent default submission for cash
            // Form should be submitted normally
        }
    });
</script>
@endsection
