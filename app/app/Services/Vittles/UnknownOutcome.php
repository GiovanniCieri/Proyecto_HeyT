<?php

namespace App\Services\Vittles;

/** Señala que un POST pudo llegar al POS aunque no tengamos una confirmación legible. */
class UnknownOutcome extends VittlesException {}
