<?php

namespace App\Enums;

enum Permission: string
{
    case ReservationsViewAny = 'reservations.view-any';
    case ReservationsView = 'reservations.view';
    case ReservationsCreate = 'reservations.create';
    case ReservationsUpdate = 'reservations.update';
    case ReservationsDelete = 'reservations.delete';
    case ReservationsConfirm = 'reservations.confirm';
    case ReservationsReject = 'reservations.reject';
    case TablesManage = 'tables.manage';
    case OperatingHoursManage = 'operating-hours.manage';
    case StaffManage = 'staff.manage';
}
