<?php

namespace App\Enums;

/**
 * Lifecycle states for an OrderSubmission.
 *
 * `null` (no row in order_submissions) means the order has not been touched
 * yet — frontend renders that as "Ny bestilling".
 */
enum SubmissionStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
}
