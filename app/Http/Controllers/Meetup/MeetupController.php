<?php

namespace App\Http\Controllers\Meetup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meetup\StoreMeetupRequest;
use App\Http\Requests\Meetup\UpdateMeetupRequest;
use App\Http\Resources\MeetupResource;
use App\Models\Meetup;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MeetupController extends Controller
{
    public function index()
    {
        $meetups = Meetup::query()
            ->where('starts_at', '>', now())
            ->with('organizer')
            ->withCount('confirmedBookings')
            ->orderBy('starts_at')
            ->paginate(10);

        return MeetupResource::collection($meetups);
    }

    public function store(StoreMeetupRequest $request)
    {
        $meetup = $request->user()
            ->organizedMeetups()
            ->create($request->validated());

        return (new MeetupResource($meetup->load('organizer')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Meetup $meetup)
    {
        $meetup->load('organizer')->loadCount('confirmedBookings');

        return new MeetupResource($meetup);
    }

    public function update(UpdateMeetupRequest $request, Meetup $meetup)
    {
        $meetup->update($request->validated());

        return new MeetupResource($meetup->load('organizer')->loadCount('confirmedBookings'));
    }

    public function organizerMeetups(User $user){
        $meetups = $user->organizedMeetups()->where('starts_at' , '>' , now())->withCount('confirmedBookings')->orderBy('starts_at')->paginate(10);
        return MeetupResource::collection($meetups);
    }

    public function destroy(Meetup $meetup)
    {
        Gate::authorize('delete', $meetup);

        $meetup->delete();

        return response()->json([
            'message' => 'Meetup deleted successfully',
        ], 200);
    }
}
