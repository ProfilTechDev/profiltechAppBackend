<?php

namespace App\Enums;

/**
 * Lifecycle states for a delivery Tour.
 *
 *   - Draft:     being assembled in admin. Departments cannot see the
 *                contained orders yet, and the order list is freely
 *                re-sortable.
 *   - Approved:  printed and sent to the floor. Sequence numbers are
 *                locked; further insertions get an A/B/C suffix so the
 *                paper labels already on packages stay valid.
 *   - Completed: all orders on the tour have been packed and dispatched.
 */
enum TourStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Completed = 'completed';
}
