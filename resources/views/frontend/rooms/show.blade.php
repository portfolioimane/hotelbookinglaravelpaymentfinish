@extends('frontend.layouts.app')

@section('content')
<div class="container mt-5">
    <div class="card">
        <img src="{{ $room->image_url }}" class="card-img-top" alt="{{ $room->name }}">
        <div class="card-body">
            <h2 class="card-title">{{ $room->name }}</h2>
            <p class="card-text">{{ $room->description }}</p>
            <p class="card-text"><strong>Price:</strong> {{ $room->price }} MAD</p>
            <p class="card-text"><strong>Capacity:</strong> {{ $room->capacity }} guests</p>
            
            <!-- Direct Book Now Button -->
            <a href="{{ route('frontend.bookings.create', $room->id) }}" class="btn btn-primary mt-3">Book Now</a>
        </div>
    </div>
</div>
@endsection
