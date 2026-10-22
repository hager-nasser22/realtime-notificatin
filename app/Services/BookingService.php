<?php
namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Events\BookingRequested;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingService{
    public function book(User $user , Meetup $meetup){
        if($meetup->organizedBy($user)){
            throw BookingException::organizerCannotBook();
        }
        if($meetup->starts_at->isPast()){
            throw BookingException::meetupStarted();
        }
        return DB::transaction(function () use ($user, $meetup) {
            $meetup = Meetup::whereKey($meetup->id)->lockForUpdate()->firstOrFail();

            $status = $meetup->requires_approval
                ? BookingStatus::Pending
                : BookingStatus::Confirmed;

            $existing = Booking::where('user_id', $user->id)
                ->where('meetup_id', $meetup->id)
                ->first();

            if ($existing?->status === BookingStatus::Rejected) {
                throw BookingException::rejected();
            }

            if ($existing && $existing->status !== BookingStatus::Cancelled) {
                throw BookingException::alreadyBooked();
            }

            if ($status === BookingStatus::Confirmed) {
                $this->ensureHasSpace($meetup);
            }

            if ($existing) {
                $existing->transitionTo($status);
                $booking = $existing;
            }else{
                $booking = Booking::create([
                    'user_id'   => $user->id,
                    'meetup_id' => $meetup->id,
                    'status'    => $status,
                ]);
            };
            $status === BookingStatus::Pending ? BookingRequested::dispatch($booking) : BookingConfirmed::dispatch($booking);
        });
    }

    public function approve(Booking $booking): Booking
    {
        $meetup = Meetup::where($booking->meetup_id)->lockForUpdate()->firstOrFail();
        $booking->refresh();
        $this->ensureHasSpace($meetup);
        $booking->transitionTo(BookingStatus::Confirmed);
        BookingConfirmed::dispatch($booking);
        return $booking;
    }

    public function reject(Booking $booking): Booking
    {
        $booking->transitionTo(BookingStatus::Rejected);
        BookingRejected::dispatch($booking);
        return $booking;
    }

    public function cancel(Booking $booking): Booking
    {
        $booking->transitionTo(BookingStatus::Cancelled);
        BookingCancelled::dispatch($booking);
        return $booking;
    }

    public function ensureHasSpace(Meetup $meetup) :void{
        if($meetup->confirmedBookings()->count() >= $meetup->capacity){
            throw BookingException::meetupFull();
        }
    }
}
