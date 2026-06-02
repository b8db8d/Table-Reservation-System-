<?php

namespace App\Http\Controllers;

use App\Models\OperatingHours;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Spatie\Honeypot\Honeypot;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $operatingHours = OperatingHours::all()
            ->keyBy('day_of_week')
            ->map(fn (OperatingHours $oh) => [
                'isClosed' => $oh->is_closed,
                'openTime' => $oh->open_time,
                'closeTime' => $oh->close_time,
            ]);

        $honeypot = app(Honeypot::class);

        return Inertia::render('Welcome', [
            'canRegister' => Features::enabled(Features::registration()),
            'operatingHours' => $operatingHours,
            'honeypot' => $honeypot->toArray(),
        ]);
    }
}
