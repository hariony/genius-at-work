<?php

declare(strict_types=1);

namespace App\Application\Crm\AnonymizeLead;

/** Anonymisation à la demande (droit à l'effacement), avant la fin de la durée de conservation. */
final readonly class AnonymizeLeadCommand
{
    /** @param string $actor identifiant de l'administrateur, pour le journal */
    public function __construct(
        public string $leadId,
        public string $actor,
    ) {
    }
}
