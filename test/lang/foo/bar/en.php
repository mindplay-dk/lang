<?php

return [
    'Hello, {world}' => 'Greetings, {world}',
    '{num} results' => function ($num) { return $num != 1 ? "{$num} results" : "{$num} result"; },
    'Rename' => 'Rename',
];
