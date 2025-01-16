<?php

/**
 * Returns the list a backups for a specific instance
 *
 * @param array{instance: string} $data
 * @return array
 * @throws Exception
 */
function instance_backups(array $data): array {
    if(!isset($data['instance'])) {
        throw new InvalidArgumentException("missing_instance", 400);
    }

    if(!is_string($data['instance']) || !instance_exists($data['instance'])) {
        throw new InvalidArgumentException("invalid_instance", 400);
    }

    $export = array_map(
        'basename',
        array_filter(glob('/home/'.$data['instance'].'/export/*'), 'is_file')
    );

    $import = array_map(
        'basename',
        array_filter(glob('/home/'.$data['instance'].'/import/*'), 'is_file')
    );

    return [
        'code' => 200,
        'body' => [
            'export' => $export,
            'import' => $import
        ]
    ];
}
