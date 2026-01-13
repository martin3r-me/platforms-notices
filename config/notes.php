<?php

return [
    'routing' => [
        'mode' => env('NOTES_MODE', 'path'),
        'prefix' => 'notes',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'notes.dashboard',
        'icon'  => 'heroicon-o-document-text',
        'order' => 40,
    ],

    'sidebar' => [
        [
            'group' => 'Notizen',
            'dynamic' => [
                'model'     => \Platform\Notes\Models\NotesFolder::class,
                'team_based' => true,
                'order_by'  => 'name',
                'route'     => 'notes.folders.show',
                'icon'      => 'heroicon-o-folder',
                'label_key' => 'name',
            ],
        ],
    ],
    'billables' => []
];
