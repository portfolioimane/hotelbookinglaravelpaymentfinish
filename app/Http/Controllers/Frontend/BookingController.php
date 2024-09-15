<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class BookingController extends Controller
{
    private $paypal;

    public function __construct()
    {
        $this->paypal = new PayPalClient;
        $this->paypal->setApiCredentials(config('paypal'));
        $this->paypal->setAccessToken($this->paypal->getAccessToken());
    }

    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())->get();
        return view('frontend.bookings.index', compact('bookings'));
    }

    public function create($roomId)
    {
        $room = Room::findOrFail($roomId);
        return view('frontend.bookings.create', compact('room'));
    }

    public function submit(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
        ]);

        $user = Auth::user();

        $booking = Booking::create([
            'room_id' => $request->room_id,
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'check_in' => $request->check_in,
            'check_out' => $request->check_out,
            'status' => 'pending',
        ]);

        return redirect()->route('frontend.bookings.confirm', $booking->id);
    }

    public function confirm($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        return view('frontend.bookings.confirm', compact('booking'));
    }

    public function selectPayment(Request $request, $bookingId)
    {
        $request->validate([
            'payment_method' => 'required|in:stripe,paypal,cash',
            'payment_method_id' => 'required_if:payment_method,stripe',
        ]);

        $booking = Booking::findOrFail($bookingId);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->payment_method === 'stripe') {
            return $this->handleStripePayment($request, $booking);
        }

        return $this->completeBooking($booking, $request->payment_method);
    }

    private function handleStripePayment(Request $request, $booking)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => 1000, // Amount in cents (e.g., 1000 cents = $10.00)
                'currency' => 'usd',
                'description' => 'Room Booking',
                'payment_method' => $request->payment_method_id,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never'
                ],
            ]);

            Log::info('Stripe PaymentIntent created', ['payment_intent' => $paymentIntent]);

            if ($paymentIntent->status === 'succeeded') {
                return $this->completeBooking($booking, 'stripe');
            } elseif ($paymentIntent->status === 'requires_action' || $paymentIntent->status === 'requires_source_action') {
                return redirect($paymentIntent->next_action->use_stripe_sdk->stripe_js);
            } else {
                throw new \Exception('Payment failed or was canceled.');
            }
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error', ['error' => $e->getMessage()]);
            abort(500, 'Payment processing error.');
        } catch (\Exception $e) {
            Log::error('General Error', ['error' => $e->getMessage()]);
            abort(500, 'Payment processing error.');
        }
    }

  public function paypalCallback(Request $request)
{
    try {
        $orderId = $request->query('token');

        // Retrieve the order details from PayPal
        $order = $this->paypal->showOrder($orderId);

        if ($order['status'] === 'COMPLETED') {
            $booking = Booking::where('id', $order['purchase_units'][0]['reference_id'])->firstOrFail();
            return $this->completeBooking($booking, 'paypal');
        } else {
            Log::error('PayPal order status is not completed', ['order' => $order]);
            throw new \Exception('PayPal order capture failed.');
        }
    } catch (\Exception $e) {
        Log::error('PayPal Callback Error', ['error' => $e->getMessage()]);
        abort(500, 'Payment processing error.');
    }
}


    private function completeBooking($booking, $paymentMethod)
    {
        $booking->status = 'confirmed';
        $booking->payment_method = $paymentMethod;
        $booking->save();

        return redirect()->route('frontend.bookings.index')->with('success', 'Booking confirmed and payment completed.');
    }
}