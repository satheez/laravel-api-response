<?php

declare(strict_types=1);

return [
    'success' => [
        'default' => 'Request completed successfully.',
        'created' => 'Resource created successfully.',
        'updated' => 'Resource updated successfully.',
        'deleted' => 'Resource deleted successfully.',
    ],

    'errors' => [
        'validation' => 'The given data was invalid.',
        'unauthorized' => 'Unauthenticated.',
        'forbidden' => 'This action is unauthorized.',
        'not_found' => 'Resource not found.',
        'invalid_request' => 'Invalid request.',
        'too_many_requests' => 'Too many requests.',
        'server_error' => 'Something went wrong. Please try again later.',
    ],
];
