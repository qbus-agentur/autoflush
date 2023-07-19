<?php

return [
    'autoflush:clearmenuforpulishedpages' => [
        'class' => \Qbus\Autoflush\Command\ClearMenuForPulishedPagesCommand::class,
        'schedulable' => true,
    ],
];
