<?php

use App\Models\JoiningGroupRestriction;
use App\Models\TableJoiningGroup;
use Carbon\Carbon;

it('can be created for a specific day of the week', function (): void {
    $restriction = JoiningGroupRestriction::factory()
        ->forDay(5) // Saturday
        ->betweenHours(19, 22)
        ->create();

    expect($restriction->day_of_week)->toBe(5)
        ->and($restriction->start_time)->toBe('19:00:00')
        ->and($restriction->end_time)->toBe('22:00:00');
});

it('can be created with null day_of_week to apply every day', function (): void {
    $restriction = JoiningGroupRestriction::factory()->everyDay()->create();

    expect($restriction->day_of_week)->toBeNull();
});

it('day_of_week is cast to integer when set', function (): void {
    $restriction = JoiningGroupRestriction::factory()->forDay(3)->create();

    expect($restriction->day_of_week)->toBe(3)->toBeInt();
});

it('belongs to a joining group', function (): void {
    $group = TableJoiningGroup::factory()->create();
    $restriction = JoiningGroupRestriction::factory()
        ->for($group, 'tableJoiningGroup')
        ->create();

    expect($restriction->tableJoiningGroup->id)->toBe($group->id);
});

it('is accessible via group restrictions relationship', function (): void {
    $group = TableJoiningGroup::factory()->create();
    JoiningGroupRestriction::factory()->for($group, 'tableJoiningGroup')->count(2)->create();

    expect($group->restrictions()->count())->toBe(2);
});

it('cascades deletion when parent group is deleted', function (): void {
    $group = TableJoiningGroup::factory()->create();
    JoiningGroupRestriction::factory()->for($group, 'tableJoiningGroup')->count(2)->create();

    $group->delete();

    expect(JoiningGroupRestriction::where('table_joining_group_id', $group->id)->count())->toBe(0);
});

describe('appliesTo', function (): void {
    it('blocks a matching day and time', function (): void {
        $restriction = JoiningGroupRestriction::factory()
            ->forDay(5) // Saturday = Carbon dayOfWeek 6, but let's use 5=Friday
            ->betweenHours(19, 22)
            ->create();

        // Friday at 20:00
        $dateTime = Carbon::parse('next Friday')->setTime(20, 0);

        expect($restriction->appliesTo($dateTime))->toBeTrue();
    });

    it('does not block a different day', function (): void {
        $restriction = JoiningGroupRestriction::factory()
            ->forDay(5) // Friday
            ->betweenHours(19, 22)
            ->create();

        // Saturday at 20:00
        $dateTime = Carbon::parse('next Saturday')->setTime(20, 0);

        expect($restriction->appliesTo($dateTime))->toBeFalse();
    });

    it('does not block outside the time window', function (): void {
        $restriction = JoiningGroupRestriction::factory()
            ->forDay(5) // Friday
            ->betweenHours(19, 22)
            ->create();

        $dateTime = Carbon::parse('next Friday')->setTime(17, 0);

        expect($restriction->appliesTo($dateTime))->toBeFalse();
    });

    it('blocks every day when day_of_week is null', function (): void {
        $restriction = JoiningGroupRestriction::factory()
            ->everyDay()
            ->betweenHours(19, 22)
            ->create();

        $monday = Carbon::parse('next Monday')->setTime(20, 0);
        $thursday = Carbon::parse('next Thursday')->setTime(20, 0);

        expect($restriction->appliesTo($monday))->toBeTrue()
            ->and($restriction->appliesTo($thursday))->toBeTrue();
    });

    it('does not block when time is exactly at end_time boundary', function (): void {
        $restriction = JoiningGroupRestriction::factory()
            ->everyDay()
            ->betweenHours(19, 22)
            ->create();

        $dateTime = Carbon::now()->setTime(22, 0);

        expect($restriction->appliesTo($dateTime))->toBeFalse();
    });
});
