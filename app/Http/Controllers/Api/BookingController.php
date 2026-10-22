<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Meetup;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings)
    {
    }

    public function index(Request $request, Meetup $meetup)
    {
        Gate::authorize('viewBookings', $meetup);

        $status = BookingStatus::tryFrom($request->query('status', ''));

        $bookings = $meetup->bookings()
            ->with('user')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return BookingResource::collection($bookings);
    }

    public function store(Request $request, Meetup $meetup)
    {
        $booking = $this->bookings->book($request->user(), $meetup);

        return (new BookingResource($booking->load('meetup')))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        return new BookingResource($this->bookings->cancel($booking));
    }

    public function approve(Booking $booking)
    {
        Gate::authorize('approve', $booking);

        return new BookingResource($this->bookings->approve($booking)->load('user'));
    }

    public function reject(Booking $booking)
    {
        Gate::authorize('reject', $booking);

        return new BookingResource($this->bookings->reject($booking)->load('user'));
    }
}
